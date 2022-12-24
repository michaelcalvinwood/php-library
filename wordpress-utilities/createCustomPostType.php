<?php

// Register post type for $pluralName
function createPostType($name, $pluralName, $theme) {
    $labels = array(
      "name"                  => _x( "$pluralName", "Post Type General Name", "$theme" ),
      "singular_name"         => _x( "$name", "Post Type Singular Name", "$theme" ),
      "menu_name"             => __( "$pluralName", "$theme" ),
      "name_admin_bar"        => __( "$name", "$theme" ),
      "archives"              => __( "Archive", "$theme" ),
      "attributes"            => __( "Attributes", "$theme" ),
      "parent_item_colon"     => __( "Parent $name", "$theme" ),
      "all_items"             => __( "All $pluralName", "$theme" ),
      "add_new_item"          => __( "Add $name", "$theme" ),
      "add_new"               => __( "Add $name", "$theme" ),
      "new_item"              => __( "New $name", "$theme" ),
      "edit_item"             => __( "Edit $name", "$theme" ),
      "update_item"           => __( "Update $name", "$theme" ),
      "view_item"             => __( "View $name", "$theme" ),
      "view_items"            => __( "View $pluralName", "$theme" ),
      "search_items"          => __( "Search $name", "$theme" ),
      "not_found"             => __( "Not Found", "$theme" ),
      "not_found_in_trash"    => __( "Not Found in Trash", "$theme" ),
      "featured_image"        => __( "Featured Image", "$theme" ),
      "set_featured_image"    => __( "Save Featured Image", "$theme" ),
      "remove_featured_image" => __( "Remove Featured Image", "$theme" ),
      "use_featured_image"    => __( "Use as Featured Image", "$theme" ),
      "insert_into_item"      => __( "Insert in $name", "$theme" ),
      "uploaded_to_this_item" => __( "Add in $name", "$theme" ),
      "items_list"            => __( "List $pluralName", "$theme" ),
      "items_list_navigation" => __( "Navigate to $pluralName", "$theme" ),
      "filter_items_list"     => __( "Filter $pluralName", "$theme" ),
    );
    $args = array(
      "label"                 => __( "$pluralName", "$theme" ),
      "description"           => __( "$pluralName for website", "$theme" ),
      "labels"                => $labels,
      "supports"              => array( "title", "editor", "thumbnail" ),
      "hierarchical"          => false,
      "public"                => true,
      "show_ui"               => true,
      "show_in_menu"          => true,
      "menu_position"         => 7,
      "menu_icon"             => "dashicons-buddicons-buddypress-logo",
      "show_in_admin_bar"     => true,
      "show_in_nav_menus"     => true,
      "can_export"            => true,
      "has_archive"           => false,
      "exclude_from_search"   => false,
      "publicly_queryable"    => true,
      "capability_type"       => "page",
    );
    register_post_type( $pluralName, $args );
  
  }

function mcwAddPosts () {
    createPostType('Event', 'Events', "flex-mag-sixspoke");
}
  
add_action( "init", "mcwAddPosts", 0 );