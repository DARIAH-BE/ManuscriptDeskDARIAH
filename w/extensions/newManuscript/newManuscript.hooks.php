<?php

/**
 * This file is part of the newManuscript extension
 * Copyright (C) 2015 Arent van Korlaar
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Arent van Korlaar <akvankorlaar'at' gmail 'dot' com> 
 * @copyright 2015 Arent van Korlaar
 * 
 * 
 * Todo: Who owns this file, who has copyright for it? Some of the functions are from Richard Davis ... 
 * This file incorporates work covered by the following copyright and
 * permission notice: 
 */
class newManuscriptHooks extends ManuscriptDeskBaseHooks {

    use HTMLCollectionMetaTable;

    /**
     * This is the newManuscriptHooks class for the NewManuscript extension. Various aspects relating to interacting with 
     * the manuscript page (and other special pages in the extension)are arranged here, 
     * such as loading the zoomviewer, loading the metatable, adding CSS modules, loading the link to the original image, 
     * making sure a manuscript page can be deleted only by the user that has uploaded it (unless the user is a sysop), and preventing users from making
     * normal wiki pages on NS_MANUSCRIPTS (the manuscripts namespace identified by 'manuscripts:' in the URL)
     */
    private $images_root_dir;
    private $page_title;
    private $page_title_with_namespace;
    private $namespace;
    private $document_root;
    private $manuscript_url_count_size;
    private $allowed_file_extensions;
    private $zoomimage_check_before_delete;
    private $original_image_check_before_delete;
    private $max_charachters_manuscript;
    private $view;
    private $creator_user_name;
    private $manuscripts_title;
    private $collection_title;
    private $out;
    private $user;
    private $title;
    private $wrapper;

    public function __construct() {
        
    }

    /**
     * Assign globals to properties
     * Creates default values when these have not been set
     */
    private function assignGlobalsToProperties() {

        global $wgLang, $wgOut, $wgNewManuscriptOptions, $wgWebsiteRoot;

        $this->manuscript_url_count_size = $wgNewManuscriptOptions['url_count_size'];
        $this->images_root_dir = $wgNewManuscriptOptions['zoomimages_root_dir'];
        $this->page_title = strip_tags($wgOut->getTitle()->getPartialURL());
        $this->page_title_with_namespace = strip_tags($wgOut->getTitle()->getPrefixedURL());
        $this->namespace = $wgOut->getTitle()->getNamespace();

        $this->allowed_file_extensions = $wgNewManuscriptOptions['allowed_file_extensions'];
        $this->max_charachters_manuscript = $wgNewManuscriptOptions['max_charachters_manuscript'];

        $this->zoomimage_check_before_delete = false;
        $this->original_image_check_before_delete = false;

        $this->view = false;

        return true;
    }

    /**
     * This function loads the zoomviewer if the editor is in edit mode. 
     */
    public function onEditPageShowEditFormInitial(EditPage $editPage, OutputPage &$out) {

        $this->setOutputPage($out);
        $this->setWrapper();

        if (!$this->manuscriptIsInEditMode() || !$this->currentPageIsAValidManuscriptPage()) {
            return true;
        }

        $html = $this->getHTMLIframeForZoomviewer();
        $out->addHTML($html);
        $out->addModuleStyles('ext.zoomviewer');
        return true;
    }

    private function currentPageIsAValidManuscriptPage() {
        $out = $this->out;
        if (!$this->isInManuscriptsNamespace($out) || !$this->manuscriptPageExists($out)) {
            return false;
        }

        return true;
    }

    private function manuscriptIsInEditMode() {
        $out = $this->out;
        $request = $out->getRequest();
        $value = $request->getText('action');

        //submit action will only be true in case the user tries to save a page with too many charachters (see '$this->max_charachters_manuscript')
        if ($value !== 'edit' || $value !== 'submit') {
            return false;
        }

        return true;
    }

    /**
     * This function loads the zoomviewer if the page on which it lands is a manuscript,
     * and if the url is valid.     
     */
    public function onMediaWikiPerformAction(OutputPage $out, Article $article, Title $title, User $user, WebRequest $request, MediaWiki $wiki) {

        $this->setOutputPage($out);
        $this->setUser($user);
        $this->setTitle($title);
        $this->setWrapper();

        if ($wiki->getAction($request) !== 'view' || !$this->currentUserIsAManuscriptEditor($user) || !$this->currentPageIsAValidManuscriptPage()) {
            return true;
        }

        $html = '';
        $html .= $this->getHTMLManuscriptView();
        $html .= $this->getHTMLIframeForZoomviewer();
        $out->addHTML($html);
        $out->addModuleStyles('ext.zoomviewer');
        return true;
    }

    private function getHTMLManuscriptView() {

        $url_without_namespace = $title->getPartialURL();
        $this->collection_title = $this->wrapper->getCollectionTitleFromUrl($url_without_namespace);
        $this->creator_user_name = $this->wrapper->getUserNameFromUrl($url_without_namespace);
        $this->manuscripts_title = $this->wrapper->getManuscriptsTitleFromUrl($url_without_namespace);

        $html = "";
        $html .= $this->getCollectionHeader();
        $html .= "<table id='link-wrap'>";
        $html .= "<tr>";
        $html .= $this->getLinkToOriginalManuscriptImage();
        $html .= $this->getLinkToEditCollection();
        $html .= $this->getPreviousNextPageLinks();
        $html .= "</tr>";
        $html .= "</table>";

        return $html;
    }

    private function getCollectionHeader() {
        $collection_title = $this->collection_title;
        if ($this->collectionTitleIsValid()) {
            return '<h2>' . htmlspecialchars($collection_title) . '</h2><br>';
        }

        return '';
    }

    private function collectionTitleIsValid() {
        $collection_title = $this->collection_title;
        if (!isset($collection_title) || empty($collection_title) || $collection_title === 'none') {
            return false;
        }

        return true;
    }

    private function getLinkToEditCollection() {

        $collection_title = $this->collection_title;
        if ($this->collectionTitleIsValid()) {
            $current_user_name = $this->user->getName();
            //only allow the owner of the collection to edit collection data
            if ($this->creator_user_name === $current_user_name) {
                return $this->getHTMLLinkToEditCollection();
            }
        }

        return '';
    }

    private function currentUserIsAManuscriptEditor(User $user) {
        if (!in_array('ManuscriptEditors', $user->getGroups())) {
            return false;
        }

        return true;
    }

    private function getHTMLLinkToEditCollection() {

        global $wgArticleUrl;

        $collection_title = $this->collection_title;
        $page_title_with_namespace = $this->title->getPrefixedURL();
        $edit_token = $this->user->getEditToken();

        $html = "";
        $html .= '<form class="manuscriptpage-form" action="' . $wgArticleUrl . 'Special:UserPage" method="post">';
        $html .= "<input class='button-transparent' type='submit' name='editlink' value='Edit Collection Metadata'>";
        $html .= "<input type='hidden' name='collection_title' value='" . $collection_title . "'>";
        $html .= "<input type='hidden' name='link_back_to_manuscript_page' value='" . $page_title_with_namespace . "'>";
        $html .= "<input type='hidden' name='edit_metadata_posted' value = 'edit_metadata_posted'>";
        $html .= "<input type='hidden' name='wpEditToken' value='$edit_token'>";
        $html .= "</form>";

        return $html;
    }

    /**
     * This function gets the links to the previous and the next page of the collection, if they exist 
     */
    private function getPreviousNextPageLinks() {

        global $wgArticleUrl;

        $page_title_with_namespace = $this->title->getPrefixedURL();
        $collection_title = $this->collection_title;
        list($previous_page_url, $next_page_url) = $this->wrapper->getPreviousAndNextPageUrl($collection_title, $page_title_with_namespace);

        $html = "";
        $html .= "<td>";

        if (isset($previous_page_url)) {
            $html .= "<a href='" . $wgArticleUrl . htmlspecialchars($previous_page_url) . "' class='link-transparent' title='Go to Previous Page'>Go to Previous Page</a>";
        }

        if (isset($previous_page_url) && isset($next_page_url)) {
            $html .= "<br>";
        }

        if (isset($next_page_url)) {
            $html .= "<a href='" . $wgArticleUrl . htmlspecialchars($next_page_url) . "' class='link-transparent' title='Go to Next Page'>Go to Next Page</a>";
        }

        $html .= "</td>";

        return $html;
    }

    /**
     * This function returns the link to the original image
     */
    private function getLinkToOriginalManuscriptImage() {

        global $wgWebsiteRoot;

        $partial_original_image_path = $this->constructPartialOriginalImagePath();
        $original_image_path = $wgWebsiteRoot . '/' . $partial_original_image_path;

        if (is_dir($original_image_path)) {
            $file_scan = scandir($original_image_path);
            $image_file = isset($file_scan[2]) ? $file_scan[2] : "";

            if ($image_file !== "") {
                $full_original_image_path = $original_image_path . '/' . $image_file;

                if ($this->isImage($full_original_image_path)) {
                    $link_original_image_path = $partial_original_image_path . '/' . $image_file;
                    return "<td><a class='link-transparent' href='$link_original_image_path' target='_blank'>" . $this->getMessage('newmanuscripthooks-originalimage') . "</a></td>";
                }
            }
        }

        return "<b>" . $this->getMessage('newmanuscripthooks-errorimage') . "</b>";
    }

    /**
     * Construct the full path of the original image
     */
    private function constructPartialOriginalImagePath() {

        global $wgNewManuscriptOptions;

        $original_images_dir = $wgNewManuscriptOptions['original_images_dir'];
        $creator_user_name = $this->creator_user_name;
        $manuscripts_title = $this->manuscripts_title;

        return $original_images_dir . '/' . $creator_user_name . '/' . $manuscripts_title;
    }

    /**
     * This function checks if the file is an image. This has been done earlier and more thouroughly when uploading, but these checks are just to make sure
     */
    private function isImage($path) {

        if (pathinfo($path, PATHINFO_EXTENSION) !== null) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if (in_array($extension, $this->allowed_file_extensions) && getimagesize($path) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates the HTML for the iframe
     */
    private function getHTMLIframeForZoomviewer() {

        global $wgScriptPath, $wgLang;

        $viewer_type = $this->getViewerType();
        $viewer_path = $this->getViewerPath();
        $image_file_path = $this->constructImageFilePath();
        $language = $wgLang->getCode();
        $website_name = 'Manuscript Desk';
        return '<iframe id="zoomviewerframe" src="' . $wgScriptPath . '/extensions/NewManuscript/' . $viewer_path . '?image=' . $image_file_path . '&amp;lang=' . $language . '&amp;sitename=' . urlencode($website_name) . '"></iframe>';
    }

    /**
     * Get the default viewer type.
     */
    private function getViewerType() {

        if ($this->browserIsInternetExplorer()) {
            return 'js';
        }

        return 'zv';
    }

    /**
     * Determines whether the browser is Internet Explorer.
     */
    private function browserIsInternetExplorer() {

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/MSIE/i', $user_agent)) {
            return true;
        }

        return false;
    }

    private function getViewerPath($viewer_type) {
        if ($viewer_type === 'js') {
            return 'tools/ajax-tiledviewer/ajax-tiledviewer.php';
        }

        return 'tools/zoomify/zoomifyviewer.php';
    }

    /**
     * Constructs the full path of the image to be passed to the iframe
     */
    private function constructImageFilePath() {

        try {

            global $wgNewManuscriptOptions;
            $images_root_dir = $wgNewManuscriptOptions['zoomimages_root_dir'];
            $url_without_namespace = $this->out->getTitle()->getPartialURL();
            $database_wrapper = $this->wrapper;
            $creator_user_name = $database_wrapper->getUserNameFromUrl($url_without_namespace);
            $manuscripts_title = $database_wrapper->getManuscriptsTitleFromUrl($url_without_namespace);
            return '/' . $images_root_dir . '/' . $user_name . '/' . $manuscripts_title . '/';
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * The function register, registers the wikitext <pagemetatable> </pagemetatable>
     * with the parser, so that the metatable can be loaded. When these tags are encountered in the wikitext, the function renderPageMetaTable
     * is called. The metatable refers to meta data on a collection level, while the pagemetatable tags enable users to insert page-specific meta data
     */
    public static function register(Parser &$parser) {

        // Register the hook with the parser
        $parser->setHook('pagemetatable', array('newManuscriptHooks', 'renderPageMetaTable'));
        return true;
    }

    /**
     * This function renders the pagemetatable, when the tags are encountered in the wikitext
     */
    public static function renderPageMetaTable($input, $args, Parser $parser) {

        $page_meta_table = new pageMetaTable();
        $page_meta_table->extractOptions($input);

        return $page_meta_table->renderTable($input);
    }

    /**
     * This function prevents users from moving a manuscript page
     * 
     * @param Title $oldTitle
     * @param Title $newTitle
     * @param User $user
     * @param type $error
     * @param type $reason
     * @return boolean
     */
    public function onAbortMove(Title $oldTitle, Title $newTitle, User $user, &$error, $reason) {

        if ($oldTitle->getNamespace() !== NS_MANUSCRIPTS) {
            return true;
        }

        $error = $this->getMessage('newmanuscripthooks-move');

        return false;
    }

    /**
     * This function runs every time mediawiki gets a delete request. This function prevents
     * users from deleting manuscripts they have not uploaded
     * 
     * @param WikiPage $article
     * @param User $user
     * @param type $reason
     * @param type $error
     */
    public function onArticleDelete(WikiPage &$article, User &$user, &$reason, &$error) {

        $this->assignGlobalsToProperties();

        $page_title = $this->page_title;
        $namespace = $this->namespace;

        if ($namespace !== NS_MANUSCRIPTS) {
            //this is not a manuscript page. Allow deletion
            return true;
        }

        $user_name = $user->getName();
        $user_groups = $user->getGroups();
        $page_title_array = explode("/", $page_title);
        $user_fromurl = isset($page_title_array[0]) ? $page_title_array[0] : null;

        if (($user_fromurl === null || $user_name !== $user_fromurl) && !in_array('sysop', $user_groups)) {
            //deny deletion because the current user did not create this manuscript, and the user is not an administrator
            $error = "<br>" . $this->getMessage('newmanuscripthooks-nodeletepermission') . ".";
            return false;
        }

        $document_root = $this->document_root;
        $images_root_dir = $this->images_root_dir;

        $filename_fromurl = isset($page_title_array[1]) ? $page_title_array[1] : null;

        $zoom_images_file = $document_root . DIRECTORY_SEPARATOR . $images_root_dir . DIRECTORY_SEPARATOR . $user_fromurl . DIRECTORY_SEPARATOR . $filename_fromurl;

        $url_count_size = $this->manuscript_url_count_size;

        //do not delete any additional files on server if the zoom images file does not exist,
        //if the url does not have the format of a manuscripts page, or if $filename_fromurl is null
        if (!file_exists($zoom_images_file) || count($page_title_array) !== $url_count_size || !isset($filename_fromurl)) {

            return true;
        }

        $this->user_fromurl = $user_fromurl;
        $this->filename_fromurl = $filename_fromurl;

        $this->deleteExportFiles($zoom_images_file);

        $this->deleteOriginalImage();

        $collection_name = $this->new_manuscript_wrapper->getCollectionTitle($this->page_title_with_namespace);
        $this->new_manuscript_wrapper->deleteDatabaseEntry($collection_name, $this->page_title_with_namespace);
        $this->new_manuscript_wrapper->subtractAlphabetnumbers($filename_fromurl, $collection_name);

        return true;
    }

    /**
     * Check if all the default files are present, and delete all files
     */
    private function deleteExportFiles($zoom_images_file) {

        $tile_group_url = $zoom_images_file . DIRECTORY_SEPARATOR . 'TileGroup0';
        $image_properties_url = $zoom_images_file . DIRECTORY_SEPARATOR . 'ImageProperties.xml';

        if (!is_dir($tile_group_url) || !is_file($image_properties_url)) {
            return false;
        }

        $this->zoomimage_check_before_delete = true;

        return $this->deleteAllFiles($zoom_images_file);
    }

    /**
     * This function checks if the original image path file is valid, and then calls deleteAllFiles()
     * 
     * @return boolean
     */
    private function deleteOriginalImage() {

        $partial_original_image_path = $this->constructPartialOriginalImagePath();
        $original_image_path = $this->document_root . $partial_original_image_path;

        if (!is_dir($original_image_path)) {
            return false;
        }

        $file_scan = scandir($original_image_path);
        $image_file = isset($file_scan[2]) ? $file_scan[2] : "";

        if ($image_file === "") {
            return false;
        }

        $full_original_image_path = $original_image_path . $image_file;

        if (!$this->isImage($full_original_image_path)) {
            return false;
        }

        if (count($file_scan) > 3) {
            return false;
        }

        $this->original_image_check_before_delete = true;

        return $this->deleteAllFiles($original_image_path);
    }

    /**
     * This function deletes all files in $zoom_images_file. First a last check is done.
     * After this the function deletes files in $path
     *  
     * @param type $path
     * @return boolean
     */
    private function deleteAllFiles($path) {

        if ($this->zoomimage_check_before_delete || $this->original_image_check_before_delete) {

            //start deleting files         
            if (is_dir($path) === true) {
                $files = array_diff(scandir($path), array('.', '..'));

                foreach ($files as $file) {
                    //recursive call
                    $this->deleteAllFiles(realpath($path) . DIRECTORY_SEPARATOR . $file);
                }

                return rmdir($path);
            }
            else if (is_file($path) === true) {
                return unlink($path);
            }
        }

        return false;
    }

    /**
     * This function prevents users from saving new wiki pages on NS_MANUSCRIPTS when there is no corresponding file in the database,
     * and it checks if the content is not larger than $max_charachters_manuscript  
     * 
     * @param type $wikiPage
     * @param type $user
     * @param type $content
     * @param type $summary
     * @param type $isMinor
     * @param type $isWatch
     * @param type $section
     * @param type $flags
     * @param type $status
     */
    public function onPageContentSave(&$wikiPage, &$user, &$content, &$summary, $isMinor, $isWatch, $section, &$flags, &$status) {

        $this->assignGlobalsToProperties();

        $page_title_with_namespace = $this->page_title;
        $page_title = $this->page_title;
        $namespace = $this->namespace;

        if ($namespace !== NS_MANUSCRIPTS) {
            //this is not a manuscript. Allow saving
            return true;
        }

        $document_root = $this->document_root;
        $images_root_dir = $this->images_root_dir;

        $page_title_array = explode("/", $page_title);

        $user_fromurl = isset($page_title_array[0]) ? $page_title_array[0] : null;
        $filename_fromurl = isset($page_title_array[1]) ? $page_title_array[1] : null;

        $zoom_images_file = $document_root . DIRECTORY_SEPARATOR . $images_root_dir . DIRECTORY_SEPARATOR . $user_fromurl . DIRECTORY_SEPARATOR . $filename_fromurl;

        if (!file_exists($zoom_images_file) || !isset($user_fromurl) || !isset($filename_fromurl)) {

            //the page is in NS_MANUSCRIPTS but there is no corresponding file in the database, so don't allow saving    
            $status->fatal(new RawMessage($this->getMessage('newmanuscripthooks-nopermission') . "."));
            return true;
        }

        $new_content = $content->mText;

        $charachters_current_save = strlen($new_content);

        //check if this page does not have more charachters than $max_charachters_manuscript
        if ($charachters_current_save > $this->max_charachters_manuscript) {

            $status->fatal(new RawMessage($this->getMessage('newmanuscripthooks-maxchar1') . " " . $charachters_current_save . " " .
                $this->getMessage('newmanuscripthooks-maxchar2') . " " . $this->max_charachters_manuscript . " " . $this->getMessage('newmanuscripthooks-maxchar3') . "."));
            return true;
        }

        //this is a manuscript page, there is a corresponding file in the database, and $max_charachters_manuscript has not been reached, so allow saving
        return true;
    }

    /**
     * This function adds additional modules containing CSS before the page is displayed
     */
    public function onBeforePageDisplay(OutputPage &$out, Skin &$ski) {

        $title_object = $out->getTitle();

        //mPrefixedText is the page title with the namespace
        $page_title_with_namespace = $title_object->mPrefixedText;

        if ($title_object->getNamespace() === NS_MANUSCRIPTS) {
            //add css for the metatable and the zoomviewer
            $out->addModuleStyles('ext.metatable');

            //meta table has to rendered here, because in this way it will be appended after the text html, and not before
            if ($this->view) {
                $this->addMetatableToManuscriptPage($out, $page_title_with_namespace);
            }
        }
        elseif ($page_title_with_namespace === 'Special:NewManuscript') {
            $out->addModuleStyles('ext.newmanuscriptcss');
            $out->addModules('ext.newmanuscriptloader');
        }

        return true;
    }

    private function addMetatableToManuscriptPage(OutputPage $out, $page_title_with_namespace) {
        $collection_title = $this->new_manuscript_wrapper->getCollectionTitle($page_title_with_namespace);

        if (empty($collection_title)) {
            return;
        }

        $meta_data = $this->getCollectionMetadata($collection_title);
        $html = $this->getHTMLCollectionMetaTable($out, $meta_data);
        $out->addHTML($html);
        return;
    }

    private function getCollectionMetadata($collection_title) {

        if (!isset($collection_title)) {
            return '';
        }

        $database_wrapper = new AllCollectionsWrapper();
        return $database_wrapper->getSingleCollectionMetadata($collection_title);
    }

    /**
     * This function visualizes <add> and <del> tags that are nested in themselves correctly. It also removes tags that are not available in the editor for visualization.
     * These tags will still be visible in the editor. 
     */
    public function onParserAfterTidy(&$parser, &$text) {

        //look for stray </add> tags, and replace them with a tei-add span element  
        $text = preg_replace('/<\/span><\/span>(.*?)&lt;\/add&gt;/', '</span></span><span class="tei-add">$1</span>', $text);

        //look for stray </del> tags, and replace them with a tei-del span element  
        $text = preg_replace('/<\/span><\/span>(.*?)&lt;\/del&gt;/', '</span></span><span class="tei-del">$1</span>', $text);

        $text = preg_replace('/<\/span><\/span>(.*?)&lt;\/hi&gt;/', '</span></span><span class="tei-hi superscript">$1</span>', $text);

        //look for any other escaped tags, and remove them
        $text = preg_replace('/&lt;(.*?)&gt;/s', '', $text);

        return true;
    }

    private function setWrapper() {

        if (isset($this->wrapper)) {
            return;
        }

        return $this->wrapper = new newManuscriptWrapper();
    }

    private function setOutputPage(OutputPage $out) {

        if (isset($this->out)) {
            return;
        }

        return $this->out = $out;
    }

    private function setUser(User $user) {

        if (isset($this->user)) {
            return;
        }

        return $this->user = $user;
    }

    private function setTitle(Title $title) {

        if (isset($this->title)) {
            return;
        }

        return $this->title = $title;
    }

}
