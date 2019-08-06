<?php
//
// Description
// -----------
// This function will generate the members page for the tenant.
//
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_customers_web_processRequestMembers(&$ciniki, $settings, $tnid, $args) {

    $uri_split = $args['uri_split'];
    
    //
    // Check if a file was specified to be downloaded
    //
    $download_err = '';
    if( isset($ciniki['tenant']['modules']['ciniki.info'])
        && isset($uri_split[0]) && $uri_split[0] == 'download'
        && isset($uri_split[1]) && $uri_split[1] != '' 
        && isset($uri_split[2]) && $uri_split[2] != '' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'fileDownload');
        $rc = ciniki_info_web_fileDownload($ciniki, $tnid, $uri_split[1], '', $uri_split[2]);
        if( $rc['stat'] == 'ok' ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            $file = $rc['file'];
            if( $file['extension'] == 'pdf' ) {
                header('Content-Type: application/pdf');
            }
//          header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
            header('Content-Length: ' . strlen($file['binary_content']));
            header('Cache-Control: max-age=0');

            print $file['binary_content'];
            exit;
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.71', 'msg'=>'We\'re sorry, but the file you requested does not exist.'));
    }

    //
    // Store the content created by the page
    // Make sure everything gets generated ok before returning the content
    //
    $content = '';
    $page_content = '';
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );
    $base_url = $args['base_url'];

    //
    // Check if we are to display a category
    //
    if( isset($uri_split[0]) && $uri_split[0] == 'category' 
        && isset($uri_split[1]) && $uri_split[1] != '' 
        ) {
        $category_permalink = $uri_split[1];

        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'memberList');
        if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] == 'thumbnail-list' ) {
            $rc = ciniki_customers_web_memberList($ciniki, $settings, $tnid, array('category'=>$category_permalink, 'format'=>'list'));
        } else {
            $rc = ciniki_customers_web_memberList($ciniki, $settings, $tnid, array('category'=>$category_permalink, 'format'=>'2dlist'));
        }
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $members = $rc['members'];

        $page_content .= "<article class='page'>\n"
            . "<header class='entry-title'><h1 class='entry-title'>$article_title</h1></header>\n"
            . "<div class='entry-content'>\n"
            . "";

        if( count($members) > 0 ) {
            $base_url = $ciniki['request']['base_url'] . "/members";
            if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] == 'thumbnail-list' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processBlockThumbnailList');
                $rc = ciniki_web_processBlockThumbnailList($ciniki, $settings, $tnid, array(
                    'anchors'=>'permalink',
                    'base_url'=>$base_url,
                    'list'=>$members,
                    ));
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
                $rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $members, array('notitle'=>'yes', 'anchors'=>'permalink'));
            }
//              array('0'=>array('name'=>'', 'list'=>$members)), array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= $rc['content'];
        } else {
            $page_content .= "<p>We're sorry, but there doesn't appear to be any members in this category.</p>";
        }

        $page_content .= "</div>"
            . "</article>"
            . "";
    }

    //
    // Check if we are to display an image, from the gallery, or latest images
    //
/*    elseif( isset($uri_split[0]) && $uri_split[0] != '' 
        && isset($uri_split[1]) && $uri_split[1] == 'gallery' 
        && isset($uri_split[2]) && $uri_split[2] != '' 
        ) {
        $member_permalink = $uri_split[0];
        $image_permalink = $uri_split[2];
        $gallery_url = $ciniki['request']['base_url'] . "/members/$member_permalink/gallery";

        //
        // Load the member to get all the details, and the list of images.
        // It's one query, and we can find the requested image, and figure out next
        // and prev from the list of images returned
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'memberDetails');
        $rc = ciniki_customers_web_memberDetails($ciniki, $settings, 
            $tnid, $member_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $member = $rc['member'];

        if( !isset($member['images']) || count($member['images']) < 1 ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.72', 'msg'=>"I'm sorry, but the image you requested does not exist."));
        }

        $first = NULL;
        $last = NULL;
        $img = NULL;
        $next = NULL;
        $prev = NULL;
        foreach($member['images'] as $iid => $image) {
            if( $first == NULL ) {
                $first = $image;
            }
            if( $image['permalink'] == $image_permalink ) {
                $img = $image;
            } elseif( $next == NULL && $img != NULL ) {
                $next = $image;
            } elseif( $img == NULL ) {
                $prev = $image;
            }
            $last = $image;
        }

        if( count($member['images']) == 1 ) {
            $prev = NULL;
            $next = NULL;
        } elseif( $prev == NULL ) {
            // The requested image was the first in the list, set previous to last
            $prev = $last;
        } elseif( $next == NULL ) {
            // The requested image was the last in the list, set previous to last
            $next = $first;
        }
    
        $article_title = "<a href='" . $ciniki['request']['base_url'] . "/members/$member_permalink'>" . $member['name'] . "</a>";
        if( $img['title'] != '' ) {
            $page_title = $member['name'] . ' - ' . $img['title'];
            $article_title .= ' - ' . $img['title'];
        } else {
            $page_title = $member['name'];
        }
    
        //
        // Load the image
        //
        if( isset($img['image_id']) && $img['image_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
            $rc = ciniki_web_getScaledImageURL($ciniki, $img['image_id'], 'original', 0, 600);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $img_url = $rc['url'];
        } else {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.73', 'msg'=>"We're sorry, but the image you requested does not exist."));
        }

        //
        // Set the page to wide if possible
        //
        $ciniki['request']['page-container-class'] = 'page-container-wide';

        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generateGalleryJavascript');
        $rc = ciniki_web_generateGalleryJavascript($ciniki, $next, $prev);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['request']['inline_javascript'] = $rc['javascript'];

        $ciniki['request']['onresize'] = "gallery_resize_arrows();";
        $ciniki['request']['onload'] = "scrollto_header();";
        $page_content .= "<article class='page'>\n"
            . "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$article_title</h1></header>\n"
            . "<div class='entry-content'>\n"
            . "";
        $page_content .= "<div id='gallery-image' class='gallery-image'>";
        $page_content .= "<div id='gallery-image-wrap' class='gallery-image-wrap'>";
        if( $prev != null ) {
            $page_content .= "<a id='gallery-image-prev' class='gallery-image-prev' href='$gallery_url/" . $prev['permalink'] . "'><div id='gallery-image-prev-img'></div></a>";
        }
        if( $next != null ) {
            $page_content .= "<a id='gallery-image-next' class='gallery-image-next' href='$gallery_url/" . $next['permalink'] . "'><div id='gallery-image-next-img'></div></a>";
        }
        $page_content .= "<img id='gallery-image-img' title='" . preg_replace("/\'/", "\\'", strip_tags($img['title'])) . "' alt='" . preg_replace("/\'/", "\\'", strip_tags($img['title'])) . "' src='" . $img_url . "' onload='javascript: gallery_resize_arrows();' />";
        $page_content .= "</div><br/>"
            . "<div id='gallery-image-details' class='gallery-image-details'>"
            . "<span class='image-title'>" . $img['title'] . '</span>'
            . "<span class='image-details'></span>";
        if( $img['description'] != '' ) {
            $page_content .= "<span class='image-description'>" . preg_replace('/\n/', '<br/>', $img['description']) . "</span>";
        }
        $page_content .= "</div></div>";
        $page_content .= "</div></article>";
    }
*/
    //
    // Check if we are to display an member
    //
    elseif( isset($uri_split[0]) && $uri_split[0] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'memberDetails');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

        //
        // Get the member information
        //
        $member_permalink = $uri_split[0];
        $rc = ciniki_customers_web_memberDetails($ciniki, $settings, $tnid, $member_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $member = $rc['member'];
        $base_url .= '/' . $member_permalink;
        $page['title'] .= ' - ' . $member['name'];
        $page['breadcrumbs'][] = array('name'=>$member['name'], 'url'=>$base_url);

        if( isset($uri_split[2]) && $uri_split[1] == 'gallery' && $uri_split[2] != '' ) {
            $image_permalink = $uri_split[2];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
            $rc = ciniki_web_galleryFindNextPrev($ciniki, $member['images'], $image_permalink);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            } 
            if( $rc['img'] == null ) {
                $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested."); 
            } else {
                $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
                if( $rc['img']['title'] != '' ) {
                    $page['title'] .= ' - ' . $rc['img']['title'];
                }
                $block = array('type'=>'galleryimage', 'section'=>'gallery-primary-image', 'primary'=>'yes', 'image'=>$rc['img']);
                if( $rc['prev'] != null ) {
                    $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id    ']);
                }
                if( $rc['next'] != null ) {
                    $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id    ']);
                }
                $page['blocks'][] = $block;
                $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'section'=>'gallery-images', 'base_url'=>$base_url . '/gallery', 'images'=>$member['images']);
            }
        } else {
            if( isset($member['image_id']) && $member['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'id'=>'aside-image', 'primary'=>'yes', 'image_id'=>$member['image_id'], 'caption'=>$member['image_caption']);
            }
            $page['blocks'][] = array('type'=>'content', 'content'=>$member['description']);

            //
            // Add contact_info
            //
            $cinfo = '';
            if( isset($member['addresses']) ) {
                foreach($member['addresses'] as $address) {
                    $addr = '';
                    if( $address['address1'] != '' ) {
                        $addr .= ($addr!=''?'<br/>':'') . $address['address1'];
                    }
                    if( $address['address2'] != '' ) {
                        $addr .= ($addr!=''?'<br/>':'') . $address['address2'];
                    }
                    if( $address['city'] != '' ) {
                        $addr .= ($addr!=''?'<br/>':'') . $address['city'];
                    }
                    if( $address['province'] != '' ) {
                        $addr .= ($addr!=''?', ':'') . $address['province'];
                    }
                    if( $address['postal'] != '' ) {
                        $addr .= ($addr!=''?'  ':'') . $address['postal'];
                    }
                    if( $addr != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . "$addr";
                    }
                }
            }
            if( isset($member['phones']) ) {
                foreach($member['phones'] as $phone) {
                    if( $phone['phone_label'] != '' && $phone['phone_number'] != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_label'] . ': ' . $phone['phone_number'];
                    } elseif( $phone['phone_number'] != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_number'];
                    }
                }
            }
            if( isset($member['emails']) ) {
                foreach($member['emails'] as $email) {
                    if( $email['email'] != '' ) {
                        $cinfo .= ($cinfo!=''?'<br/>':'') . '<a href="mailto:' . $email['email'] . '">' . $email['email'] . '</a>';
                    }
                }
            }

            if( $cinfo != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'member-contact', 'title'=>'Contact Info', 'content'=>$cinfo);
            }

            if( isset($member['links']) && count($member['links']) > 0 ) {
                $page['blocks'][] = array('type'=>'links', 'section'=>'member-links', 'title'=>'Website' .  (count($member['links']) > 1 ? 's' : ''), 'links'=>$member['links']);
            }
            // Add gallery
            if( isset($member['images']) && count($member['images']) > 0 ) {
                $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'section'=>'additional-images', 'base_url'=>$base_url . '/gallery', 'images'=>$member['images']);
            }
        }
    }

    //
    // Display the list of members if a specific one isn't selected
    //
    else {
        if( isset($settings['page-members-categories-display']) 
            && ($settings['page-members-categories-display'] == 'wordlist'
                || $settings['page-members-categories-display'] == 'wordcloud' )
            && isset($ciniki['tenant']['modules']['ciniki.customers']['flags']) 
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x04) > 0 
            ) {
            
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'tagCloud');
            $rc = ciniki_customers_web_tagCloud($ciniki, $settings, $tnid, 40);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            //
            // Process the tags
            //
            if( $settings['page-members-categories-display'] == 'wordlist' ) {
                if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
                    $page['blocks'][] = array('type'=>'taglist', 'section'=>'member-categories', 'base_url'=>$base_url, 'tags'=>$rc['tags']);
                } else {
                    $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, there are no categories");
                }
            } elseif( $settings['page-members-categories-display'] == 'wordcloud' ) {
                if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
                    $page['blocks'][] = array('type'=>'tagcloud', 'section'=>'member-categories', 'base_url'=>$base_url, 'tags'=>$rc['tags']);
                } else {
                    $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, there are no members found");
                }
            }
        } else {
            //
            // Display the list of members
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'memberList');
            if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] == 'thumbnail-list' ) {
                $rc = ciniki_customers_web_memberList($ciniki, $settings, $tnid, array('format'=>'list'));
            } else {
                $rc = ciniki_customers_web_memberList($ciniki, $settings, $tnid, array('format'=>'2dlist'));
            }
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $members = $rc['members'];

            if( count($members) > 0 ) {
                if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] == 'thumbnail-list' ) {
                    $page['blocks'][] = array('type'=>'thumbnaillist', 'base_url'=>$base_url, 'anchors'=>'permalink', 'list'=>$members);
                } else {
                    $page['blocks'][] = array('type'=>'cilist', 'section'=>'member-list', 'base_url'=>$base_url, 'notitle'=>'yes', 'categories'=>$members);
                }
//                  array('0'=>array('name'=>'', 'list'=>$members)), array());
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            } else {
                if( !isset($settings['page-members-membership-details']) 
                    || $settings['page-members-membership-details'] != 'yes' 
                    ) {
                    $page['blocks'][] = array('type'=>'content', 'content'=>'Currently no members.');
                }
            }

        }
    
        if( isset($settings['page-members-membership-details']) && $settings['page-members-membership-details'] == 'yes' ) {
            $add_membership_info = 'yes';
        } elseif( isset($settings['page-members-application-details']) && $settings['page-members-application-details'] == 'yes' ) {
            $add_membership_info = 'yes';
        }
    }

    if( isset($add_membership_info) && $add_membership_info == 'yes' ) {
        //
        // Pull the membership info from the ciniki.info module
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'pageDetails');
        if( isset($add_membership_info) && $add_membership_info == 'yes' ) {
            $rc = ciniki_info_web_pageDetails($ciniki, $settings, $tnid, 
                array('content_type'=>'7'));
        } elseif( isset($add_application_info) && $add_application_info == 'yes' ) {
            $rc = ciniki_info_web_pageDetails($ciniki, $settings, $tnid, 
                array('content_type'=>'17'));
        }
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $info = $rc['content'];

        if( isset($info['image_id']) && $info['image_id'] != '' && $info['image_id'] != 0 ) {
            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'id'=>'aside-image', 'primary'=>'yes', 'image_id'=>$info['image_id']);
        }
        $page['blocks'][] = array('type'=>'content', 'title'=>'Membership Information', 'content'=>$info['content']);

        if( isset($info['files']) ) {
            $page['blocks'][] = array('type'=>'files', 'base_url'=>$base_url . '/download', 'files'=>$info['files']);
        }
    }

    //
    // Generate the complete page
    //
    $submenu = array();
    if( isset($settings['page-members-categories-display']) && $settings['page-members-categories-display'] == 'submenu' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'tagCloud');
        $base_url = $ciniki['request']['base_url'] . '/members/category';
        $rc = ciniki_customers_web_tagCloud($ciniki, $settings, $tnid, 40);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
            foreach($rc['tags'] as $tag) {
                $submenu[] = array(
                    'name' => $tag['name'],
                    'url' => $ciniki['request']['base_url'] . '/members/category/' . $tag['permalink'],
                    );
            }
        }
    }


    return array('stat'=>'ok', 'page'=>$page);
} 
?>
