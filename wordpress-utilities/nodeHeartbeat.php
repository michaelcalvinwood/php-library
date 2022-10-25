<?php
/*
 * Add the following to functions.php
 */

function postToNode($data, $url, $port = 80, $connectionTimeout = 3, $responseTimeout = 10, $debug = false) {
  $payload = json_encode($data);

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_PORT, $port);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectionTimeout);   
  curl_setopt($ch, CURLOPT_TIMEOUT, $responseTimeout);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($payload))
  );
  
  $result = curl_exec($ch);
  
  if ($result === false && $debug) echo curl_error($ch);

  curl_close($ch);

  return $result;
}

function run_on_all_job_status_transitions( $new_status, $old_status, $post ) {
  if ($post->post_status !== 'publish' && $post->post_status !== 'trash' && $post->post_status !== 'inherit') return;

  $data = [];
  $data["newStatus"] = $new_status;
  $data["oldStatus"] = $old_status;
  $data["post"] = $post;

  $heartbeat = postToNode($data, "https://services.pymnts.com/heartbeat", 5005);

  // for debugging. Remove from production after testing.
  if ($heartbeat === false) header('heartbeat: false');
  else header('heartbeat: true');

  return;
  /*
   * Alertnatively send select information to node
   */

  //$info = [];
  // $info["newStatus"] = $new_status;
  // $info["oldStatus"] = $old_status;
  //$info["id"] = $post->ID;
  //$info["date"] = $post->post_date;
  //$info["title"] = $post->post_title;
  //$info["excerpt"] = $post->post_excerpt;
  //$info["content"] = $post->post_content;
  //$info["status"] = $post->post_status;
  //$info["modified"] = $post->post_modified;
  //$info["modifiedGmt"] = $post->post_modified_gmt;
  //$info["type"] = $post->post_type;

  // link
  //$info['link'] = get_permalink($post->ID);

  // author
  //$info["author"] = get_the_author_meta('display_name', $post->post_author);
  //$info["authorId"] = $post->post_author;

  // tags
  //$info["tags"] = get_the_tags($post->ID);

  // category
  //$info["category"] = get_the_category($post->ID);

  // featured media
  //$info["mediaId"] = get_post_thumbnail_id($post->ID);
  // if ($info["mediaId"]) {
  //   $sizes = get_intermediate_image_sizes();
  //   $numSizes = count($sizes);
  //   $info["media"] = [];
  //   for ($i = 0; $i < $numSizes; ++$i) $info["media"][$sizes[$i]] = get_the_post_thumbnail_url($post->ID, $sizes[$i]);
  // }
  
  //$heartbeat = postToNode($info, "https://services.pymnts.com/heartbeat", 5005);

  //if ($heartbeat === false) header('heartbeat: false');
  //else header('heartbeat: true');
}
add_action( 'transition_post_status', 'run_on_all_job_status_transitions', 10, 3 );
