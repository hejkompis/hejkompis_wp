<?php
	
	class Pocket {

		private $name,
				$excerpt,
				$url, 
				$image, 
				$tags = [],
				$timestamp;

		function __construct($object = false) {
			
			if($object->given_title) {
				$this->name = $object->given_title;
			}
			elseif($object->resolved_title) {
				$this->name = $object->resolved_title;
			}
			else {
				$this->name = $object->resolved_url;
			}
			$this->excerpt 		= $object->excerpt;
			$this->url 			= $object->resolved_url;
			$this->timestamp 	= $object->time_added;
			if(isset($object->image) && is_object($object->image)) {
				$this->image 	= $object->image->src;
			}
			if(isset($object->tags) && is_object($object->tags)) {
				foreach($object->tags as $tag) {
					array_push($this->tags, $tag->tag);
				}
			}

		}

		function __get($var) {
			if ($this->$var) {
				return $this->$var;
			}
		}

		function __isset($var) { 
			if ($this->$var) {
				return TRUE; 
			}
			return FALSE; 
		}

		// *** CURL functions *** //
		
		function curl_get($url, $headers = false, $request = 'get', $data = false) {

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			if($request === 'post') {
				
				if(is_array($data)) {
					$fields = '';
					foreach ($data as $key => $value) {
					    $fields .= $key . '=' . $value . '&';
					}
					rtrim($fields, '&');

					curl_setopt($ch, CURLOPT_POST, count($data));
	    			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				}

				else {
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				}
				
			}

			if($headers) {
				$headers = !is_array($headers) ? array($headers) : $headers;
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			$output = curl_exec($ch); 

			return json_decode($output);
		
		}

		function get_image_data($url) {

			$ch = curl_init($url);
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			
			$data = curl_exec($ch);
			curl_close($ch);
			
			return $data;
			
		}

		// *** Pocket fetch *** //

		function get_all() {

			if(!is_page('pocket')) { return; }

			$pocket_posts = [];

			$url = 'https://getpocket.com/v3/get?consumer_key=50971-c998235ca8644c65abc85bab&access_token=bacf9a9c-868b-ba06-253b-f4eb09&detailType=complete';

			$data = $this->curl_get($url);
			
			foreach($data->list as $item) {
				
				$pocket_posts[] = new Pocket($item);
				
			}

			// echo '<pre>';
			// 	print_r($data->list);
			// echo '</pre>';

			// die;

			$pocket_posts = array_reverse($pocket_posts);

			$data = array(
				"numberposts" => -1,
				'meta_query' => array(
					array(
						'key' => 'source',
						'value' => 'pocket',
					)
				)
			);
			$all_current_posts = get_posts($data);
			
			$all_wordpress_categories = get_categories();
			
			$current_urls = [];
			$current_categories = [];

			// loopa igenom posterna en och en
			foreach($all_current_posts as $current_post) {

				// spara ner id:t i en variabel
				$id = $current_post->ID;
				// hämta postens metadata för external url (kan vara flera)
				$post_external_urls = get_post_meta($id, "external_url") ? get_post_meta($id, "external_url") : [];

				// loopa igenom alla metadata för externa url:er
				foreach($post_external_urls as $post_external_url) {
					array_push($current_urls, $post_external_url);		
				}				
			
			}

			foreach($all_wordpress_categories as $wordpress_category) {
			 	$current_categories[$wordpress_category->term_id] = $wordpress_category->slug;				
			}

			$count = 0;

			foreach($pocket_posts as $key => $values) {

				if($count >= 25) { die; }

				$data = [];

				$post_exists = in_array($values->url, $current_urls) ? true : false;

				if(!$post_exists) {

					$count++;

					// spara kategori om den inte redan finns
					// 1. ta fram pocket-taggar och gör om till array
					$post_tags = [];
					foreach($values->tags as $tag) {
						$post_tags[] = preg_replace('/[^\00-\255]+/u', '', $tag);
					}

					// 2. jämför båda arrayer
					$new_categories = array_diff($post_tags, $current_categories);
					
					// 3. om det finns taggar från pocket som inte existerar så skall de sparas ner först för att vi ska kunna spara id:n med posten
					if($new_categories) {

						foreach($new_categories as $new_category) {

							$saved_category_id = wp_create_category(ucfirst($new_category));
							$new_category_from_wp = get_category($saved_category_id);
							$current_categories[$saved_category_id] = $new_category_from_wp->slug;

						}

					}

					$post_categories = array_intersect($current_categories, $post_tags);
					$post_category_ids = [];

					foreach($post_categories as $post_category_id => $post_category) {
						array_push($post_category_ids, $post_category_id);
					}

					$post_content = $values->excerpt;

					// 4. lägg in alla kategories id:n i post-data

					// spara ner post
					$data = array(
						"post_title" 		=> $values->name,
						"post_content" 		=> $post_content,
						"post_category"		=> $post_category_ids,
						"post_date" 		=> date("Y-m-d", $values->timestamp)."T".date("H:i:s", $values->timestamp),
						"post_status" 		=> "publish",
						"tags_input" 		=> array('pocket')
					);

					$saved_post_id = wp_insert_post($data);

					add_post_meta($saved_post_id, "source", "pocket");
					add_post_meta($saved_post_id, "external_url", $values->url);
					array_push($current_urls, $values->url);

					if($values->image != '' && @GetImageSize($values->image)) {

						add_post_meta($saved_post_id, "external_image", $values->image);

						$filename = explode('/', $values->image);
						$filename = array_reverse($filename);
						$filename = explode('?', $filename[0]);
						$filename = $filename[0];

						$uploaddir = wp_upload_dir();
						$uploadfile = $uploaddir['path'] . '/' . $filename;

						$contents = $this->get_image_data($values->image);
						$savefile = fopen($uploadfile, 'w');
						fwrite($savefile, $contents);
						fclose($savefile);

						$wp_filetype = wp_check_filetype(basename($filename), null );

						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => $filename,
							'post_content' => '',
							'post_status' => 'inherit'
						);

						$attach_id = wp_insert_attachment( $attachment, $uploadfile );

						$imagenew = get_post( $attach_id );
						$fullsizepath = get_attached_file( $imagenew->ID );
						$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
						wp_update_attachment_metadata( $attach_id, $attach_data );

						set_post_thumbnail( $saved_post_id, $attach_id );

					}

				}

			}

			die;

		}

	}