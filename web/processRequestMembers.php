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
//        && isset($uri_split[2]) && $uri_split[2] != '' 
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
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.406', 'msg'=>'We\'re sorry, but the file you requested does not exist.'));
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
    $display = '';
    if( $args['page_title'] == '' ) {
        $page['title'] = 'Members';
    }
    if( count($args['breadcrumbs']) == 0 && $args['page_title'] == '' ) {
        $page['breadcrumbs'][] = array('name'=>'Members', 'url'=>$args['base_url']);
    }

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-members-thumbnail-format']) && $settings['page-members-thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $settings['page-members-thumbnail-format'];
        if( isset($settings['page-members-thumbnail-padding-color']) && $settings['page-members-thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $settings['page-members-thumbnail-padding-color'];
        } 
    }

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

        if( isset($rc['tag_name']) && $rc['tag_name'] != '' ) {
            $page['title'] .= ' - ' . $rc['tag_name'];
            $page['breadcrumbs'][] = array('name'=>$rc['tag_name'], 'url'=>$args['base_url'] . '/category/' . $category_permalink);
        }

        //
        // Check if member selected
        //
        if( isset($uri_split[2]) && $uri_split[2] != '' ) {
            $display = 'member';
            $member_permalink = $uri_split[2];
            if( isset($uri_split[4]) && $uri_split[3] == 'gallery' && $uri_split[3] != '' ) {
                $image_permalink = $uri_split[4];
            }
        } 
        elseif( count($members) > 0 ) {
            $base_url = $args['base_url'] . '/category/' . $category_permalink;
            if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] == 'thumbnail-list' ) {
                $page['blocks'][] = array('type'=>'thumbnaillist', 
                    'base_url'=>$base_url, 
                    'anchors'=>'permalink', 
                    'list'=>$members,
                    'thumbnail_format'=>$thumbnail_format, 
                    'thumbnail_padding_color'=>$thumbnail_padding_color,
                    );
            } else {
                $page['blocks'][] = array('type'=>'cilist', 
                    'section'=>'member-list', 
                    'base_url'=>$base_url, 
                    'notitle'=>'yes', 
                    'categories'=>$members,
                    'thumbnail_format'=>$thumbnail_format, 
                    'thumbnail_padding_color'=>$thumbnail_padding_color,
                    );
            }
        } 
        else {
            $page['blocks'][] = array('type'=>'content', 'content'=>"We're sorry, but there doesn't appear to be any members in this category.");
        }
    }

    //
    // Check if we are to display an member
    //
    elseif( isset($uri_split[0]) && $uri_split[0] != '' ) {
        //
        // Get the member information
        //
        $member_permalink = $uri_split[0];
        $display = 'member';
        if( isset($uri_split[2]) && $uri_split[1] == 'gallery' && $uri_split[2] != '' ) {
            $image_permalink = $uri_split[2];
        }
    }
    //
    // Display the list of members if a specific one isn't selected
    //
    else {
        if( isset($settings['page-members-categories-display']) 
            && ($settings['page-members-categories-display'] == 'wordlist'
                || $settings['page-members-categories-display'] == 'wordcloud' )
            && ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x04)
            ) {
            
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'tagCloud');
            $rc = ciniki_customers_web_tagCloud($ciniki, $settings, $tnid, 40);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            //
            // Process the tags
            //
//            if( $args['page_title'] == '' ) {
//                $page['breadcrumbs'][] = array('name'=>'Members', 'url'=>$args['base_url']);
//            }
            if( $settings['page-members-categories-display'] == 'wordlist' ) {
                if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
                    $page['blocks'][] = array('type'=>'buttonlist', 'section'=>'member-categories', 'base_url'=>$base_url . '/category', 'tags'=>$rc['tags']);
                } else {
                    $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, there are no categories");
                }
            } elseif( $settings['page-members-categories-display'] == 'wordcloud' ) {
                if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
                    $page['blocks'][] = array('type'=>'tagcloud', 'section'=>'member-categories', 'base_url'=>$base_url . '/category', 'tags'=>$rc['tags']);
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
//                if( $args['page_title'] == '' ) {
//                    $page['breadcrumbs'][] = array('name'=>'Members', 'url'=>$args['base_url']);
//                }
                if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] == 'thumbnail-list' ) {
                    $page['blocks'][] = array('type'=>'thumbnaillist', 'base_url'=>$base_url, 'anchors'=>'permalink', 
                        'list'=>$members,
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
                } else {
                    $page['blocks'][] = array('type'=>'cilist', 'section'=>'member-list', 'base_url'=>$base_url, 'notitle'=>'yes', 
                        'categories'=>$members,
                        'jslink'=>(!isset($settings['page-members-list-format'])||$settings['page-members-list-format']!='shortbio-links'?'yes':'no'),
                        'thumbnail_format'=>$thumbnail_format, 
                        'thumbnail_padding_color'=>$thumbnail_padding_color,
                        );
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

    if( $display == 'member' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'memberDetails');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

//        if( $args['page_title'] == '' ) {
//            $page['breadcrumbs'][] = array('name'=>'Members', 'url'=>$args['base_url']);
//        }
        $rc = ciniki_customers_web_memberDetails($ciniki, $settings, $tnid, $member_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $member = $rc['member'];
        $base_url .= '/' . $member_permalink;
        $page['title'] .= ' - ' . $member['name'];
        $page['breadcrumbs'][] = array('name'=>$member['name'], 'url'=>$base_url);

        if( isset($image_permalink) && $image_permalink != '' ) {
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
                if( isset($rc['prev']['image_id']) ) {
                    $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                }
                if( isset($rc['next']['image_id']) ) {
                    $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
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
            //
            // Show Exhibition items
            //
            //
            // Add exhibition items from AGS module
            //
            if( isset($member['exhibits']) && count($member['exhibits']) > 0 ) {
                foreach($member['exhibits'] as $exhibit) {
                    $page['blocks'][] = array('type'=>'tradingcards', 'base_url'=>$exhibit['base_url'], 'section'=>'member-exhibits', 'title'=>$exhibit['name'], 'cards'=>$exhibit['items']);
                }
            }
        }
    }

    if( $display == '' && isset($add_membership_info) && $add_membership_info == 'yes' ) {
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

//        if( isset($info['image_id']) && $info['image_id'] != '' && $info['image_id'] != 0 ) {
//            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'id'=>'aside-image', 'primary'=>'yes', 'image_id'=>$info['image_id']);
//        }
        $page['blocks'][] = array(
            'type' => 'content', 
            'wide' => 'yes',
            'title' => 'Membership Information', 
            'aside_image_id' => (isset($info['image_id']) && $info['image_id'] > 0 ? $info['image_id'] : 0),
            'aside_image_caption' => (isset($info['image_caption']) ? $info['image_caption'] : ''),
            'content' => $info['content'],
            );

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
