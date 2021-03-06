<?php
/*
 * @version $Id$
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLA Class
**/
class SLA extends CommonDBTM {

   // From CommonDBTM
   var $dohistory                      = true;

   static protected $forward_entity_to = array('SLT');

   static $rightname                   = 'sla';

   static function getTypeName($nb=0) {
      // Acronymous, no plural
      return __('SLA');
   }

   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('SLT', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function cleanDBonPurge() {

      $slt = new SLT();
      $slt->cleanDBonItemDelete('SLA', $this->fields['id']);
   }

   /**
    * Print the sla form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
   **/
   function showForm($ID, $options=array()) {

      $rowspan = 1;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", array('value' => $this->fields["name"]));
      echo "<td rowspan='".$rowspan."'>".__('Comments')."</td>";
      echo "<td rowspan='".$rowspan."'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   function getSearchOptions() {

      $tab                        = array();
      $tab['common']              = __('Characteristics');

      $tab[1]['table']            = $this->getTable();
      $tab[1]['field']            = 'name';
      $tab[1]['name']             = __('Name');
      $tab[1]['datatype']         = 'itemlink';
      $tab[1]['massiveaction']    = false;

      $tab[2]['table']            = $this->getTable();
      $tab[2]['field']            = 'id';
      $tab[2]['name']             = __('ID');
      $tab[2]['massiveaction']    = false;
      $tab[2]['datatype']         = 'number';

      return $tab;
   }

   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since version 0.85
   **/
   static function getMenuContent() {

      $menu = array();
      if (Config::canUpdate()) {
            $menu['title']                              = SLA::getTypeName(Session::getPluralNumber());
            $menu['page']                               = '/front/sla.php';
            $menu['links']['search'] = '/front/sla.php';
            $menu['links']['add']    = '/front/sla.form.php';

            $menu['options']['slt']['title']           = SLT::getTypeName(Session::getPluralNumber());
            $menu['options']['slt']['page']            = '/front/slt.php';
            $menu['options']['slt']['links']['search'] = '/front/slt.php';

            $menu['options']['slalevel']['title']           = SlaLevel::getTypeName(Session::getPluralNumber());
            $menu['options']['slalevel']['page']            = '/front/slalevel.php';
            $menu['options']['slalevel']['links']['search'] = '/front/slalevel.php';

      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }

}
?>
