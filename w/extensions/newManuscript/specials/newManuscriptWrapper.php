<?php
/**
 * This file is part of the newManuscript extension
 * Copyright (C) 2015 Arent van Korlaar
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Arent van Korlaar <akvankorlaar 'at' gmail 'dot' com> 
 * @copyright 2015 Arent van Korlaar
 */

class newManuscriptWrapper{
  
  private $user_name; 
  private $maximum_pages_per_collection; 

  //class constructor
  public function __construct($user_name = "", $maximum_pages_per_collection = null){
    
    $this->user_name = $user_name;
    $this->maximum_pages_per_collection = $maximum_pages_per_collection; 
  }
  
 /**
   * This function retrieves the collections of the current user
   *  
   * @return type
   */
  public function getCollectionsCurrentUser(){
    
    $dbr = wfGetDB(DB_SLAVE);
    
    $user_name = $this->user_name; 
    $collections_current_user = array();
    
     //Database query
    $res = $dbr->select(
     'manuscripts', //from
     array(
       'manuscripts_collection',//values
        ),
     array(
     'manuscripts_user = ' . $dbr->addQuotes($user_name), //conditions
     ),
     __METHOD__,
     array(
       'ORDER BY' => 'manuscripts_collection',
     )
     );

    //while there are results
    while ($s = $res->fetchObject()){
      
      //add to the $collections_current_user array, when the collection does not equal "", "none", and when the collection is not already in the array
      if($s->manuscripts_collection !== "" && $s->manuscripts_collection !== "none" && !in_array($s->manuscripts_collection, $collections_current_user)){
        $collections_current_user[] = $s->manuscripts_collection;
      }
    }    
    
    return $collections_current_user; 
  }
  
  /**
   * This functions checks if the collection already reached the maximum allowed manuscript pages
   * 
   * @param type $posted_collection
   * @return string
   */
  public function checkNumberOfPagesPostedCollection($posted_collection){
    
    $dbr = wfGetDB(DB_SLAVE);
 
    $conds = 
        
      //Database query
    $res = $dbr->select(
      'manuscripts', //from
      array(
        'manuscripts_url',//values
         ),
      array(
      'manuscripts_user = ' . $dbr->addQuotes($this->user_name), //conditions
      'manuscripts_collection = ' . $dbr->addQuotes($posted_collection),
      ),
      __METHOD__,
      array(
        'ORDER BY' => 'manuscripts_lowercase_title',
      )
      );
        
    if ($res->numRows() > $this->maximum_pages_per_collection){
      return 'newmanuscript-error-collectionmaxreached';
    }
   
    return ""; 
  }
  
  /**
   * This function insert data into the manuscripts table
   * 
   * @param type $posted_title
   * @param type $user_name    
   * @param type $new_page_url
   * @return boolean
   */
  public function writeToDB($posted_title, $collection, $user_name,$new_page_url){
      
    $date = date("d-m-Y H:i:s");  
    $date2 = date('YmdHis');

    $lowercase_title = strtolower($posted_title);
    $lowercase_collection = strtolower($collection);
    
    $dbw = wfGetDB(DB_MASTER);
    $dbw->insert('manuscripts', //select table
      array( //insert values
      'manuscripts_id'                   => null,
      'manuscripts_title'                => $posted_title,
      'manuscripts_user'                 => $user_name,
      'manuscripts_url'                  => $new_page_url,
      'manuscripts_date'                 => $date,
      'manuscripts_lowercase_title'      => $lowercase_title,
      'manuscripts_collection'           => $collection, 
      'manuscripts_lowercase_collection' => $lowercase_collection,  
      'manuscripts_datesort'             => $date2,
       ),__METHOD__,
       'IGNORE' );
    if ($dbw->affectedRows()){
    //insert succeeded
      return true;
      
    }else{
    //return error
      return false;      
    }
  }
}