<?php
/**
 * @package Plugin hotoMeta for Joomla! 3
 * @author John Moelholt
 * @copyright (C) 2018 - HOMETOLL Open Business Software
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

// no direct access
defined( '_JEXEC' ) or die;

jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.environment.uri' );




class plgContentHotoMeta extends JPlugin
{

    function hotoReplaceAtLabels( $fieldcontext, &$row ) {

        // does the sting contain @@, only if do replace
        $pos = strpos( $fieldcontext, '@@' );
        if ($pos !== false) {
            $atlabels = array (
                '@@ID@@',
                '@@TITLE@@',
                '@@ALIAS@@',
                '@@AUTHOR@@',
                '@@INTROTEXT@@',
                '@@CREATEDBYALIAS@@',
                '@@CREATED@@',
                '@@MODIFIED@@',
                '@@METAKEY@@',
                '@@METADESC@@',
                '@@HITS@@',
                '@@METAAUTHOR@@',
                '@@METAREFERENCES@@',
                '@@CATEGORYTITLE@@',
                '@@CATEGORYALIAS@@',
                '@@IMAGEINTRO@@',
                '@@IMAGEFULLTEXT@@',
                '@@URLA@@',
                '@@URLB@@',
                '@@URLC@@'
            );

            $aimages = json_decode($row->images);
            $aurls = json_decode($row->urls);
            $jroot = JURI::root();

            $atvalues = array (
                $row->id,
                $row->title,
                $row->alias,
                $row->author,
                $row->introtext,
                $row->created_by_alias,
                $row->created,
                $row->modified,
                $row->metakey,
                $row->metadesc,
                $row->hits,
                $row->metadata->get('author'),
                $row->xreference,
                $row->category_title,
                $row->category_alias,
                $jroot . $aimages->image_intro,
                $jroot . $aimages->image_fulltext,
                $aurls->urla,
                $aurls->urlb,
                $aurls->urlc
            );

            //html safe output
            foreach( $atvalues as &$v) {
                $v = htmlspecialchars($v);
            }
            unset($v);

            $fieldcontext = str_replace( $atlabels, $atvalues, $fieldcontext );
        }

        // html safe
        return $fieldcontext;
    }


    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        $document = JFactory::getDocument();
        $hotometa_tablabel_array = explode( ',', $this->params->get('hotometa_tablabel'));

        // is this an article that is displayed
        if ( JRequest::getVar( 'view' ) == 'article' )

        // does the article has custom fields
        if ( is_array( $row->jcfields ) || is_object( $row->jcfields ) )
        foreach ( $row->jcfields as $field )
        {
            //echo "<pre>";
            //var_dump($this);
            //var_dump($document);
            //var_dump($params);
            //var_dump($context);
            //var_dump($row);
            //echo "</pre>";

            if (in_array($field->group_title, $hotometa_tablabel_array)) {

                if ( ( $this->params->get('hotometa_usecurl') == '1' ) and ( $field->title == $this->params->get('hotometa_curl') ) ) {

                    if ( $field->rawvalue !== '' ) {

                        $hotometavar = '<link rel="canonical" href="';
                        $hotometavar .= $field->rawvalue;
                        $hotometavar .= '" />';

                        $document->addCustomTag($hotometavar);
                    }
                }
                elseif ( ($field->rawvalue !== '' ) or ( $this->params->get('hotometa_outputempty') == 'Yes' ) ) {

                    $hotometavar = '<meta ';

                    // is there a override of meta name
                    $t = ''. $field->params->get("label_render_class");
                    if ($t !== '') {
                        $hotometavar .= $t;
                    } else {
                        $hotometavar .= $this->params->get('hotometa_namelabel');
                    }

                    $hotometavar .= '="';
                    $hotometavar .= $field->title;
                    $hotometavar .= '" ';

                    // is there a override of meta description
                    $t = ''. $field->params->get("render_class");
                    if ($t !== '') {
                        $hotometavar .= $t;
                    } else {
                        $hotometavar .= $this->params->get('hotometa_contentlabel');
                    }

                    $hotometavar .= '="';

                    switch ($field->type) {
                        case 'media':
                            $pos = strpos($field->rawvalue, '://');

                            if (($pos === false) and ($field->rawvalue !== '')) {
                                $baseurl = JURI::root();
                                $hotometavar .= $baseurl;
                            }
                            $hotometavar .= $field->value ? $field->rawvalue : $field->default_value;
                        break;

                        default:
                            $hotometavar .= str_replace('"', '&quot;', $field->value ? $field->rawvalue : $field->default_value);
                        break;
                    }

                    // closing the meta tag
                    $hotometavar .= '" />';

                    // replace all @@ labels
                    $hotometavar = $this->hotoReplaceAtLabels($hotometavar, $row);

                    //output the custom tag to the html header as html safe
                    $document->addCustomTag($hotometavar);

                }
            }
        }

        return;
    }
}
