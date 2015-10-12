<?php
/**
 * This file is part of the collate extension
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

class SpecialStylometricAnalysis extends SpecialPage {
  
  public $article_url; 
  
  private $minimum_collections;
  private $maximum_collections; 
  private $minimum_pages_per_collection; 
  private $user_name;  
  private $full_manuscripts_url; 
  private $collection_array;
  private $error_message;
  private $manuscripts_namespace_url;
  private $redirect_to_start;
  private $max_length;
   
  //class constructor
  public function __construct(){
    
    global $wgNewManuscriptOptions, $wgArticleUrl, $wgStylometricAnalysisOptions;  
    
    $this->article_url = $wgArticleUrl;
    $this->manuscripts_namespace_url = $wgNewManuscriptOptions['manuscripts_namespace'];
    $this->minimum_collections = $wgStylometricAnalysisOptions['wgmin_stylometricanalysis_collections'];  
    $this->maximum_collections = $wgStylometricAnalysisOptions['wgmax_stylometricanalysis_collections']; 
    $this->minimum_pages_per_collection = $wgStylometricAnalysisOptions['minimum_pages_per_collection']; 
    $this->error_message = false; //default value    
    $this->redirect_to_start = false;
    $this->variable_not_validated = false; //default value
    $this->collection_array = array();
    
    $this->max_length = 50; 

    parent::__construct('StylometricAnalysis');
	}
  
  /**
   * This function loads requests when a user submits the StylometricAnalysis form
   * 
   * @return boolean
   */
  private function loadRequest(){
    
    $request = $this->getRequest();
        
    //if the request was not posted, return false
    if(!$request->wasPosted()){
      return false;  
    }
    
    $posted_names = $request->getValueNames();    
     
    //identify the button pressed
    foreach($posted_names as $key=>$checkbox){
      
      //remove the numbers from $checkbox to see if it matches to 'collection', 'collection_hidden', or 'redirect_to_start'
      $checkbox_without_numbers = trim(str_replace(range(0,9),'',$checkbox));

      if($checkbox_without_numbers === 'collection'){
        $this->collection_array[$checkbox] = (array)$this->validateInput(json_decode($request->getText($checkbox)));    
                  
      }elseif($checkbox_without_numbers === 'redirect_to_start'){
        $this->redirect_to_start = true; 
        break;      
      }
    }
    
    if($this->variable_not_validated === true){
      return false; 
    }
    
    if($this->redirect_to_start){
      return false; 
    }
        
    return true; 
  }
  
  /**
   * This function validates input sent by the client
   * 
   * @param type $input
   */
  private function validateInput($input){
    
    if(is_array($input) || is_object($input)){
      
      foreach($input as $index => $value){
        $status = $this->validateInput($value);
        
        if(!$status){
          return false; 
        }
      }
      
      return $input; 
    }
    
    //see if one or more of these sepcial charachters match
    if(!preg_match('/^[a-zA-Z0-9:\/]*$/', $input)){
      $this->variable_not_validated = true; 
      return false; 
    }
    
    //check for empty variables or unusually long string lengths
    if($input === null || strlen($input) > 500){
      $this->variable_not_validated = true; 
      return false; 
    }
    
    return $input; 
  }
  
  /**
   * This function determines if the user has the right permissions. If a valid request was posted, this request is processed. Otherwise, the default page is shown 
   */
  public function execute(){
    
    $out = $this->getOutput();
    $user_object = $this->getUser();    
    
    if(!in_array('ManuscriptEditors',$user_object->getGroups())){
      return $out->addHTML($this->msg('stylometricanalysis-nopermission'));
    }
      
    $user_name = $user_object->getName();
    $this->user_name = $user_name; 
    
    $this->full_manuscripts_url = $this->manuscripts_namespace_url . $this->user_name . '/';
    
    $request_was_posted = $this->loadRequest();
    
    if($request_was_posted){
      return $this->processRequest();
    }
    
    return $this->prepareDefaultPage($out);   
  }
  
  /**
   * Processes the request when a user has submitted the form
   * 
   * @return type
   */
  private function processRequest(){
      
    //perhaps change this to something that is specific to the second request
    
    if(count($this->collection_array) < $this->minimum_collections){
      return $this->showError('stylometricanalysis-error-fewcollections');
    }

    if(count($this->collection_array) > $this->minimum_collections){
      return $this->showError('stylometricanalysis-error-manycollections');
    }

    return $this->showStylometricAnalysisForm();
    
    //if secondary request ...
    
    
                       
    //next screen should always be a display of your selected texts, and the form
       
    //in this screen enable users to select 3 options: only use your words, only use the calculated words, use both. 
     
    //they can also choose to go back, run a PCA analysis or a clustering analysis
      
    //only after clicking clustering analysis or PCA analysis, the texts should be assembled 
    
//    $texts = $this->constructTexts();
//    
//    //if returned false, one of the posted pages did not exist
//    if(!$texts){
//      return $this->showError('stylometricanalysis-error-notexists');
//    }
    
  }
  
      
  /**
   * This function prepares the default page, in case no request was posted
   * 
   * @return type
   */
  private function prepareDefaultPage($out){
    
    $stylometric_analysis_wrapper = new stylometricAnalysisWrapper($this->user_name, $this->minimum_pages_per_collection);   
    $collection_urls = $stylometric_analysis_wrapper->checkForManuscriptCollections();
        
    //check if the total number of collections is less than the minimum
    if(count($collection_urls) < $this->minimum_collections){
                
      $article_url = $this->article_url;
      
      $html = "";
      $html .= $this->msg('stylometricanalysis-fewcollections');    
      $html .= "<p><a class='stylometricanalysis-transparent' href='" . $article_url . "Special:NewManuscript'>Create a new collection</a></p>";
      
      return $out->addHTML($html);     
    }
   
    return $this->showDefaultPage($collection_urls, $out);    
	}
  
  /**
   * This function fetches the correct error message, and redirects to showDefaultPage()
   * 
   * @param type $type
   */
  private function showError($type){
    
    $error_message = $this->msg($type);
       
    $this->error_message = $error_message;    
    
    return $this->prepareDefaultPage($this->getOutput());
  }
  
 /**
  * This function adds html used for the begincollate loader (see ext.begincollate)
  * 
  * Source of the gif: http://preloaders.net/en/circular
  */
  private function addStylometricAnalysisLoader(){
    
    //shows after submit has been clicked
    $html  = "<div id='stylometricanalysis-loaderdiv'>";
    $html .= "<img id='stylometricanalysis-loadergif' src='/w/extensions/collate/specials/assets/362.gif' style='width: 64px; height: 64px;"
        . " position: relative; left: 50%;'>"; 
    $html .= "</div>";
    
    return $html; 
  }
  
  /**
   * This function constructs and shows the stylometric analysis form
   */
  private function showStylometricAnalysisForm(){
    
    $article_url = $this->article_url; 
    $collection_array = $this->collection_array;
    $max_length = $this->max_length; 
    $out = $this->getOutput();
    
    $collections_message = $this->constructCollectionsMessage($collection_array); 
    
    $out->setPageTitle($this->msg('stylometricanalysis-options')); 
    
    $html = "";
    $html .= "<a href='" . $article_url . "Special:StylometricAnalysis' class='link-transparent' title='Go Back'>Go Back</a>";
    $html .= "<br><br>";
    $html .= $this->msg('stylometricanalysis-chosencollections') . $collections_message . "<br>"; 
    $html .= $this->msg('stylometricanalysis-chosencollection2');   
    $html .= "<br><br>";
    
    $out->addHTML($html);
    
    $descriptor = array();
    
    $descriptor['removenonalpha'] = array(
      'label' => 'Remove non-alpha',
      'class' => 'HTMLCheckField',
    );
    
    $descriptor['lowercase'] = array(
      'label' => 'Lowercase',
      'class' => 'HTMLCheckField',
    );
    
     $descriptor['tokenizer'] = array(
      'label' => 'Tokenizer',
      'class' => 'HTMLSelectField',
      'options' => array( 
        'Whitespace' => 'Whitespace',
        'Option 2' => 2,
      ),
      'default' => 'Whitespace',
    );
     
    $descriptor['minimumsize'] = array(
      'label' => 'Minimum Size',
      'class' => 'HTMLIntField',
      'default' => 0, 
      'size' => 5, //display size
      'maxlength'=> 5, //input size
      'min' => 0,  
      'max' => 10000, 
    );
    
    $descriptor['maximumsize'] = array(
      'label' => 'Maximum Size',
      'class' => 'HTMLIntField',
      'default' => 10000, 
      'size' => 5, //display size
      'maxlength'=> 5, //input size
      'min' => 10000,  
      'max' => 20000, 
    );
    
    $descriptor['segmentsize'] = array(
      'label' => 'Segment Size',
      'class' => 'HTMLIntField',
      'default' => 0, 
      'size' => 5, //display size
      'maxlength'=> 5, //input size
      'min' => 0,  
      'max' => 10000, 
    );
    
    $descriptor['stepsize'] = array(
      'label' => 'Step Size',
      'class' => 'HTMLIntField',
      'default' => 0, 
      'size' => 5, //display size
      'maxlength'=> 5, //input size
      'min' => 0,  
      'max' => 10000, 
    );
    
    $descriptor['removepronouns'] = array(
      'label' => 'Remove Pronouns',
      'class' => 'HTMLCheckField',
    );
    
    //add field for 'remove these items too'
    
    $descriptor['vectorspace'] = array(
      'label' => 'Vector Space',
      'class' => 'HTMLSelectField',
      'options' => array( 
        'tf'        => 'tf',
        'tf_scaled' => 'tf_scaled',
        'tf_std'    => 'tf_std',
        'tf_idf'    => 'tf_idf',
        'bin'       => 'bin'
      ),
      'default' => 'tf',
    );
    
    $descriptor['featuretype'] = array(
      'label' => 'Feature Type',
      'class' => 'HTMLSelectField',
      'options' => array( 
        'word'       => 'word',
        'char'       => 'char',
        'char_wb'    => 'char_wb',
      ),
      'default' => 'word',
    );
    
    $descriptor['mfi'] = array(
      'label' => 'MFI',
      'class' => 'HTMLIntField',
      'default' => 100, 
      'size' => 5, //display size
      'maxlength'=> 5, //input size
      'min' => 0,  
      'max' => 10000, 
    );
    
    $descriptor['minimumdf'] = array(
      'class' => 'HTMLFloatField',
      'label' => 'Minimum DF',
      'default' => 0.00, 
      'size' => 5,
      'maxlength'=> 5, 
      'min' => 0, 
      'max' => 1 
    );
    
    $descriptor['maximumdf'] = array(
      'class' => 'HTMLFloatField',
      'label' => 'Minimum DF',
      'default' => 0.90, 
      'size' => 5, 
      'maxlength'=> 5, 
      'min' => 0, 
      'max' => 1 
    );
       
    $html_form = new HTMLForm($descriptor, $this->getContext());
    $html_form->setSubmitText($this->msg('stylometricanalysis-submit'));
    $html_form->addHiddenField('collection_array', json_encode($collection_array));
    $html_form->setSubmitCallback(array('SpecialStylometricAnalysis', 'processInput'));  
    $html_form->show();
  }
  
    /**
     * Callback function. Makes sure the page is redisplayed in case there was an error. 
     * 
     * @param type $formData
     * @return string|boolean
     */
  static function processInput($form_data){ 
    return false; 
  }
  
  /**
   * This function constructs the collections message
   * 
   * @param type $collection_array
   * @return type
   */
  private function constructCollectionsMessage($collection_array){
    
    $collection_name_array = array();
    
    foreach($collection_array as $index=>$small_url_array){
      $collection_name_array[] = $small_url_array['collection_name'];
    }
    
    return implode(',',$collection_name_array) . ".";
  }
   
  /**
   * This function constructs the HTML for the default page
   * 
   * @param type $collection_urls
   * @param type $out
   */
  private function showDefaultPage($collection_urls, $out){
    
    $article_url = $this->article_url; 
    
    $out->setPageTitle($this->msg('stylometricanalysis-welcome'));
    
    $about_message = $this->msg('stylometricanalysis-about');
    $version_message = $this->msg('stylometricanalysis-version');  
    $software_message = $this->msg('stylometricanalysis-software');
    $lastedit_message = $this->msg('stylometricanalysis-lastedit');
    
    $html  = "<table id='stylometricanalysis-infobox'>";
    $html .= "<tr><th>$about_message</th></tr>";
    $html .= "<tr><td>$version_message</td></tr>";
    $html .= "<tr><td>$software_message <a href= '' target='_blank'>    </a>.</td></tr>";
    $html .= "<tr><td id='stylometricanalysis-td'><small>$lastedit_message</small></td></tr>";
    $html .= "</table>";
    
    $html .= "<p>" . $this->msg('stylometricanalysis-instruction1') . '</p>';
    
    $html .= "<div id='javascript-error'></div>"; 
            
    //display the error 
    if($this->error_message){     
      $error_message = $this->error_message;  
      $html .= "<div class = 'error'>$error_message</div>";
    }
            
    $html .= "<form id='stylometricanalysis-form' action='" . $article_url . "Special:StylometricAnalysis' method='post'>";    
    $html .= "<h3>" . $this->msg('stylometricanalysis-collectionheader') . "</h3>";
       
    $html .= "<table class='stylometricanalysis-table'>";

    $a = 0;
    $html .= "<tr>";
    
    foreach($collection_urls as $collection_name=>$small_url_array){

      if(($a % 4) === 0){  
        $html .= "</tr>";
        $html .= "<tr>";    
      }

      $manuscripts_urls = $small_url_array['manuscripts_url'];
      $manuscripts_urls['collection_name'] = $collection_name; 

      foreach($manuscripts_urls as $index=>&$url){
        $url = htmlspecialchars($url);
      }
      
      //encode the array into json to be able to place it in the checkbox value
      $json_small_url_array = json_encode($manuscripts_urls);       
      $manuscript_pages_within_collection = htmlspecialchars(implode(', ',$small_url_array['manuscripts_title']));   
      $collection_text = $this->msg('stylometricanalysis-contains') . $manuscript_pages_within_collection . '.';

      //add a checkbox for the collection
      $html .="<td>";
      $html .="<input type='checkbox' class='stylometricanalysis-checkbox' name='collection$a' value='$json_small_url_array'>" . htmlspecialchars($collection_name);
      $html .= "<br>";
      $html .= "<span class='stylometricanalysis-span'>" . $collection_text . "</span>"; 
      $html .="</td>";
      $a = ++$a; 
    }

    $html .= "</tr>";
    $html .= "</table>";
  
    $html .= "<br><br>"; 
    
    $submit_hover_message = $this->msg('stylometricanalysis-hover');
    $submit_message = $this->msg('stylometricanalysis-submit');
    
    $html .= "<input type='submit' disabled id='stylometricanalysis-submitbutton' title = $submit_hover_message value=$submit_message>";   
    $html .="</form>";   
    $html .= "<br>";  
    
    $html .= $this->addStylometricAnalysisLoader();
        
    $out->addHTML($html);  
  }
}
