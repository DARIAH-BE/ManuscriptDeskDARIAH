<?php

/**
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
/**
 * This file contains example configuration settings that can be appended to the standard mediawiki localsettings.php file. It contains additional settings
 * needed, for example, to load the additional extensions, configure the namespaces, configure the user permissions and settings, and set the global configuration settings
 * for some of the extensions
 */
##############################################################
#CUSTOM CONFIGURATION SETTINGS
##############################################################
#####Misc Settings#####
//path to the extensions folder
//website root. This is used to locate the zoomImages and initialUpload directories. The full path to the website root must be specified here. 
$wgWebsiteRoot = '/path/to/website/root';
$wgExtensionAssetsPath = $wgWebsiteRoot . '/' . $wgScriptPath . '/extensions/';

//these files are autoloaded to enable the extensions
require_once( $wgExtensionAssetsPath . 'WikiEditor/WikiEditor.php');
require_once( $wgExtensionAssetsPath . 'ManuscriptDeskBase/ManuscriptDeskBase.php' );
require_once( $wgExtensionAssetsPath . 'JBTEIToolbar/JBTEIToolbar.php' );
require_once( $wgExtensionAssetsPath . 'TEITags/TEITags.php' );
require_once( $wgExtensionAssetsPath . 'Collate/Collate.php');
require_once( $wgExtensionAssetsPath . 'NewManuscript/NewManuscript.php');
require_once( $wgExtensionAssetsPath . 'SummaryPages/SummaryPages.php');
require_once( $wgExtensionAssetsPath . 'StylometricAnalysis/StylometricAnalysis.php');
require_once( $wgExtensionAssetsPath . 'HelperScripts/HelperScripts.php');
require_once( $wgExtensionAssetsPath . 'ManuscriptDeskImages/ManuscriptDeskImages.php' );
require_once( $wgExtensionAssetsPath . 'TEIExport/TEIExport.php');

//$wgArticlePath is the base url that is used to create all internal links 
$wgArticlePath = "/md/$1";
//Enable use of pretty URLs
$wgUsePathInfo = true;
$wgArticleUrl = "/md/";

// Notes from Transcribe Bentham Developers: BP Enabled by default. We need to switch this off because the bentham modern template is not HTML 5
// HTML Tidy will complain that script tags do not contain a 'type' attribute Html->inlineScript()
$wgHtml5 = false;

$wgExternalLinkTarget = '_blank';

//Enables use of WikiEditor by default 
$wgDefaultUserOptions['usebetatoolbar'] = 1;

//do not allow the following charachters in usernames
$wgInvalidUsernameCharacters = '~!@#%^&*()_+=.-|{}[]"/?<>,:;ÀÁÅÃÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ';

#####Skin Settings#####
//skin settings. The skin used is largely based on the 'cbptranscriptionenhanced' skin, with slight modifications 
$wgValidSkinNames['cbptranscriptionenhanced'] = "CbpTranscriptionEnhanced";
$wgDefaultSkin = 'cbptranscriptionenhanced';
$wgLocalStylePath = $IP . '/skins/';
require_once( $wgLocalStylePath . 'CbpTranscriptionEnhanced/CbpTranscriptionEnhanced.php' );

#####Namespace Settings#####
//define new namespaces for manuscripts and collations
define("NS_MANUSCRIPTS", 3100);
//define("NS_MANUSCRIPTS_TALK",3101);

define("NS_COLLATIONS", 3200);
//define("NS_COLLATIONS_TALK",3201);

define('NS_STYLOMETRICANALYSIS', 3300);

$wgExtraNamespaces[NS_MANUSCRIPTS] = 'Manuscripts';
//$wgExtraNamespaces[NS_MANUSCRIPTS_TALK] = 'Manuscripts_talk';
//permission "editmanuscripts" required to edit the Manuscripts namespace
$wgNamespaceProtection[NS_MANUSCRIPTS] = array('editmanuscripts');

$wgExtraNamespaces[NS_COLLATIONS] = 'Collations';
//$wgExtraNamespaces[NS_COLLATIONS_TALK] = 'Collations_talk';
//permission "editcollations" required to edit the Collations namespace
$wgNamespaceProtection[NS_COLLATIONS] = array('editcollations');

$wgExtraNamespaces[NS_STYLOMETRICANALYSIS] = 'Stylometricanalysis';
//$wgExtraNamespaces[NS_COLLATIONS_TALK] = 'Collations_talk';
//permission "editcollations" required to edit the Collations namespace
$wgNamespaceProtection[NS_STYLOMETRICANALYSIS] = array('editstylometricanalysis');

//set default searching
$wgNamespacesToBeSearchedDefault = array(
  NS_MAIN => true,
  NS_TALK => false,
  NS_USER => false,
  NS_USER_TALK => false,
  NS_PROJECT => false,
  NS_PROJECT_TALK => false,
  NS_IMAGE => false,
  NS_IMAGE_TALK => false,
  NS_MEDIAWIKI => false,
  NS_MEDIAWIKI_TALK => false,
  NS_TEMPLATE => false,
  NS_TEMPLATE_TALK => false,
  NS_HELP => false,
  NS_HELP_TALK => false,
  NS_CATEGORY => false,
  NS_CATEGORY_TALK => false,
  NS_MANUSCRIPTS => true,
  // NS_MANUSCRIPTS_TALK => false,
  NS_COLLATIONS => true,
  // NS_COLLATIONS_TALK  => false,
  NS_STYLOMETRICANALYSIS => true,
);

$wgNamespaceProtection[NS_HELP] = array('edithelp');
$wgNamespacesWithSubpages[NS_HELP] = true;

#####user permissions#####
//first, disable many of the default permissions for anonymous and registered normal users
$wgGroupPermissions['*']['edittype'] = false;
$wgGroupPermissions['*']['edithelp'] = false;
$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['createpage'] = false;
$wgGroupPermissions['*']['createtalk'] = false;
$wgGroupPermissions['*']['editmyusercss'] = false;
$wgGroupPermissions['*']['editmyuserjs'] = false;
$wgGroupPermissions['*']['createaccount'] = true;

$wgGroupPermissions['user']['edit'] = false;
$wgGroupPermissions['user']['createpage'] = false;
$wgGroupPermissions['user']['createtalk'] = false;
$wgGroupPermissions['user']['editmyusercss'] = false;
$wgGroupPermissions['user']['editmyuserjs'] = false;
$wgGroupPermissions['user']['editmyuserjs'] = false;
$wgGroupPermissions['user']['minoredit'] = false;
$wgGroupPermissions['user']['movefile'] = false;
$wgGroupPermissions['user']['move'] = false;
$wgGroupPermissions['user']['move-subpages'] = false;
$wgGroupPermissions['user']['move-rootuserpages'] = false;
$wgGroupPermissions['user']['reupload-shared'] = false;
$wgGroupPermissions['user']['reupload'] = false;
$wgGroupPermissions['user']['purge'] = false;
$wgGroupPermissions['user']['upload'] = false;
$wgGroupPermissions['user']['writeapi'] = false;

$wgGroupPermissions['autoconfirmed']['editsemiprotected'] = false;

//then, make a new group and give it most of the permissions first disabled. Users can then be manually added by a sysop to this group once they have created an account
$wgGroupPermissions['ManuscriptEditors']['edit'] = true;
$wgGroupPermissions['ManuscriptEditors']['createpage'] = true;
$wgGroupPermissions['ManuscriptEditors']['createtalk'] = true;
$wgGroupPermissions['ManuscriptEditors']['minoredit'] = true;
$wgGroupPermissions['ManuscriptEditors']['reupload-shared'] = true;
$wgGroupPermissions['ManuscriptEditors']['reupload'] = true;
$wgGroupPermissions['ManuscriptEditors']['purge'] = true;
$wgGroupPermissions['ManuscriptEditors']['upload'] = true;
$wgGroupPermissions['ManuscriptEditors']['writeapi'] = true;
$wgGroupPermissions['ManuscriptEditors']['delete'] = false;
$wgGroupPermissions['ManuscriptEditors']['editmanuscripts'] = true;
$wgGroupPermissions['ManuscriptEditors']['editcollations'] = true;
$wgGroupPermissions['ManuscriptEditors']['editstylometricanalysis'] = true;

//set additional permissions for sysops, and ensure that sysops have the same base permissions as ManuscriptEditors 
$wgGroupPermissions['sysop']['edittype'] = true;
$wgGroupPermissions['sysop']['edithelp'] = true;

$wgGroupPermissions['sysop']['edit'] = true;
$wgGroupPermissions['sysop']['createpage'] = true;
$wgGroupPermissions['sysop']['createtalk'] = true;
$wgGroupPermissions['sysop']['editmyusercss'] = true;
$wgGroupPermissions['sysop']['editmyuserjs'] = true;
$wgGroupPermissions['sysop']['editmyuserjs'] = true;
$wgGroupPermissions['sysop']['minoredit'] = true;
//although sysops are given permission to move pages, moving pages in NS_COLLATIONS and NS_MANUSCRIPTS is not allowed (it will return an error if you try to do this)
$wgGroupPermissions['sysop']['movefile'] = true;
$wgGroupPermissions['sysop']['move'] = true;
$wgGroupPermissions['sysop']['move-subpages'] = true;
$wgGroupPermissions['sysop']['move-rootuserpages'] = true;
$wgGroupPermissions['sysop']['reupload-shared'] = true;
$wgGroupPermissions['sysop']['reupload'] = true;
$wgGroupPermissions['sysop']['purge'] = true;
$wgGroupPermissions['sysop']['upload'] = true;
$wgGroupPermissions['sysop']['writeapi'] = true;
$wgGroupPermissions['sysop']['delete'] = true;
$wgGroupPermissions['sysop']['editmanuscripts'] = true;
$wgGroupPermissions['sysop']['editcollations'] = true;
$wgGroupPermissions['sysop']['editstylometricanalysis'] = true;


#####Url Hooks#####
//set a hook to change the sidebar for users that are not in the ManuscriptEditors group
$wgHooks['SkinBuildSidebar'][] = 'onSkinBuildSidebar';

/**
 * This function removes pages from the navigation sidebar if the user does not have the correct permissions to use these pages
 */
function onSkinBuildSidebar(Skin $skin, &$bar) {

    global $wgUser;

    if (in_array('ManuscriptEditors', $wgUser->getGroups()) || in_array('sysop', $wgUser->getGroups())) {
        return true;
    }

    //these elements correspond to 'New Manuscript', 'Collate Manuscripts', 'Stylometric Analysis' and 'My User Page'
    unset($bar['navigation'][1]);
    unset($bar['navigation'][2]);
    unset($bar['navigation'][3]);
    unset($bar['navigation'][4]);

    return true;
}

//set a hook to change the link to the user page (link on the top bar when clicking on the user name)
$wgHooks['PersonalUrls'][] = 'onPersonalUrls';

/**
 * This function changes the link to the user page depending on which group the user is in
 */
function onPersonalUrls(array &$personal_urls, Title $title, SkinTemplate $skin) {

    global $wgArticleUrl, $wgUser;

    //do not display the link to Special:UserPage if the user is not in the ManuscriptEditors group
    if (!in_array('ManuscriptEditors', $wgUser->getGroups())) {
        unset($personal_urls['userpage']);
        return true;
    }

    //if the current user is in the ManuscriptEditors group, change the link      
    $personal_urls['userpage']['href'] = $wgArticleUrl . 'Special:UserPage';
    return true;
}

//do not allow users to perform their own javascript (only javascript sent from server allowed)
$wgAllowUserJs = false;
//do not allow users to perform their own css (only css sent from server allowed)
$wgAllowUserCss = false;
//do not allow users to change the preferred language
$wgHiddenPrefs[] = 'language';
//do not allow users to change the preferred skin
$wgHiddenPrefs[] = 'skin';
//do not allow users to disable the toolbar 
$wgHiddenPrefs[] = 'showtoolbar';
//do not allow users to use the experimental feature 'live preview'
$wgHiddenPrefs[] = 'uselivepreview';
//do not allow users to change the image size on description pages or thumbnail size
$wgHiddenPrefs[] = 'imagesize';
$wgHiddenPrefs[] = 'thumbsize';
//do not allow users to change the edit box size
$wgHiddenPrefs[] = 'rows';
$wgHiddenPrefs[] = 'cols';
//do not allow users to change the editing toolbar
$wgHiddenPrefs[] = 'usebetatoolbar';

#####Personal Settings#####

//Primary disk. Primary location of the website and the images  
$wgPrimaryDisk = 'main disk here (for example C or /)';
$wgOriginalImagesPath = '/full/path/to/original/images/somewhere/outside/of/the/website/root';
$wgZoomImagesPath = '/full/path/to/zoomimages/somewhere/outside/of/the/website/root';
$wgPerlPath = 'perl'; //works if you can use 'perl path/to/perl/script.pl'. Alternative: /usr/bin/perl' for unix
$wgPythonPath = 'python'; //works if you can use 'python path/to/python/script.py' in terminal
$wgPystylPath = 'path/to/pystyl'; //full/path/to/pystyl

//global configuration settings that are used within the 'collate' extension
$wgCollationOptions = array(
  'collatex_url' => 'localhost:7369/collate', //url of the collatex server
  'collatex_headers' => array("Content-type: application/json; charset=UTF-8;",
    "Accept: application/json"), //headers that are sent to collatex
  'wgmin_collation_pages' => 2, //the minimum number of single manuscript pages that users are allowed to collate
  'wgmax_collation_pages' => 5, //the maximum number of single manuscript pages that users are allowed to collate
  'tempcollate_hours_before_delete' => 2, //hours before entries are deleted from the 'tempcollate' table 
);

//global configuration settings that are used within the 'newmanuscript' extension
$wgNewManuscriptOptions = array(
  'allowed_file_extensions' => array('jpg', 'jpeg', 'JPG', 'JPEG'), //allowed file extensions 
  'max_manuscripts' => 300, //maximum allowed manuscript pages per user
  'maximum_pages_per_collection' => 50, //maximum allowed pages for a collection
  'max_upload_size' => 8388608, //maximum upload size in bytes (8 mb --> 8*1024*1024). Important: this value should be lower than or equal to upload_max_filesize in php.ini    
  'slicer_path' => '/w/extensions/NewManuscript/specials/slicer.pl', //path to the slicer
  'max_on_page' => 10, //maximum entries shown on a page part of the 'summaryPages' extension, except for Special:RecentManuscriptPages
  'max_recent' => 30, //maximum entries shown on Special:RecentManuscriptPages
  'max_charachters_manuscript' => 7500, //a limitation of 5000 charachters has been set for manuscript pages, so that when performing text algorithms on the manuscript pages, the server does not receive too much input
);

$wgStylometricAnalysisOptions = array(
  'wgmin_stylometricanalysis_collections' => 2, //minimum number of collections to be able to do stylometric analysis
  'wgmax_stylometricanalysis_collections' => 5, //maximum number of collections to be able to do stylometric analysis
  'minimum_pages_per_collection' => 10, //minimum pages within a collection to be able to do stylometric analysis
  'min_mfi' => 20, //minimum number of most frequent items
  'min_words_collection' => 100, //minimum words in a collection
  'tempstylometricanalysis_hours_before_delete' => 2, //hours before entries are deleted from the 'tempstylometricanalysis' table 
  'pystyl_path' => 'path/to/pystyl/',
);

$wgHelperScriptsOptions = array(
  'deleter_ip' => '000.000.000.00', //ip-address that is allowed to delete in Special:HelperScripts
  'deleter_passphrase' => '', //password or phrase for deletion in Special:HelperScripts
  'delete_available' => 'off', //switch to 'on' if you want to be able to use the delete function in Special:HelperScripts
);

//sends complete stack trace to output in case of an uncaught exceptions. This should never be set to true on a production server
$wgShowExceptionDetails = true;

//making sure these paths do not end with a slash
$wgOriginalImagesPath = rtrim($wgOriginalImagesPath, '/');
$wgZoomImagesPath = rtrim($wgZoomImagesPath, '/');
$wgPystylPath = rtrim($wgPystylPath, '/');
$wgWebsiteRoot = rtrim($wgWebsiteRoot, '/');