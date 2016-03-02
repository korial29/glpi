<?php
/*
 * @version $Id: $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

/**
 * Update from 0.90 to 0.90.1
 *
 * @return bool for success (will die for most error)
**/
function update090to0902() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.90.2'));
   $migration->setVersion('0.90.2');


   $backup_tables = false;
   $newtables     = array();

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if (TableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.",
                                 true);
   }

   // Add missing fill in 0.90 empty version
   $migration->addField("glpi_entities", 'inquest_duration', "integer", array('value' => 0));

   /** ************ New SLA structure ************ */
   if (!TableExists('glpi_slts')) {
      $query = "CREATE TABLE `glpi_slts` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `resolution_time` int(11) NOT NULL,
                  `calendars_id` int(11) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `definition_time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `end_of_working_day` tinyint(1) NOT NULL DEFAULT '0',
                  `date_creation` datetime DEFAULT NULL,
                  `slas_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `calendars_id` (`calendars_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `slas_id` (`slas_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "0.91 add table glpi_slts");

      // Sla migration
      $query = "SELECT *
                FROM `glpi_slas`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "INSERT INTO `glpi_slts`
                          (`id`, `name`,`entities_id`, `is_recursive`, `type`, `comment`, `resolution_time`, `calendars_id`, `date_mod`, `definition_time`, `end_of_working_day`, `date_creation`, `slas_id`)
                         VALUES ('".$data['id']."', '".$data['name']."', '".$data['entities_id']."', '".$data['is_recursive']."', '".SLT::TTR."', '".addslashes($data['comment'])."', '".$data['resolution_time']."', '".$data['calendars_id']."', '".$data['date_mod']."', '".$data['definition_time']."', '".$data['end_of_working_day']."', '".date('Y-m-d H:i:s')."', '".$data['id']."');";
               $DB->queryOrDie($query, "SLA migration to SLT");
            }
         }
      }

      // Delete deprecated fields of SLA
      foreach (array('resolution_time', 'calendars_id', 'definition_time', 'end_of_working_day') as $field) {
         $migration->dropField('glpi_slas', $field);
      }

      // Slalevels changes
      $migration->changeField('glpi_slalevels', 'slas_id', 'slts_id', 'integer');

      // Ticket changes
      $migration->changeField("glpi_tickets", "slas_id", "slt_ttr", "integer");
      $migration->addField("glpi_tickets", "slt_tto", "integer", array('after' => 'slt_ttr'));
      $migration->addField("glpi_tickets", "time_to_own", "datetime", array('after' => 'due_date'));
      $migration->addKey('glpi_tickets', 'slt_tto');
      $migration->addKey('glpi_tickets', 'time_to_own');

      // Unique key for slalevel_ticket
      $migration->addKey('glpi_slalevels_tickets', array('tickets_id', 'slalevels_id'), 'unicity', 'UNIQUE');

      // Sla rules criterias migration
      $DB->queryOrDie("UPDATE `glpi_rulecriterias` SET `criteria` = 'slt_ttr' WHERE `criteria` = 'slas_id'", "SLA rulecriterias migration");

      // Sla rules actions migration
      $DB->queryOrDie("UPDATE `glpi_ruleactions` SET `field` = 'slt_ttr' WHERE `field` = 'slas_id'", "SLA ruleactions migration");
   }

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
?>