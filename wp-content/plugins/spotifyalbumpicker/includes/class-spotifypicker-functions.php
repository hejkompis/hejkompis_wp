<?php
	
	class Spotify {

		private $name,
				$url, 
				$image, 
				$tags = [], 
				$timestamp;

		function __construct($object = false) {

			$this->name 		= $object['name'];
			$this->url 			= $object['url'];
			$this->timestamp 	= strtotime($object['timestamp']);
			$this->image 		= $object['image'];	
			$this->tags 		= [$object['tag']];
		
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

		private function get_credentials($data) {

			$credentials = $this->get_spotify_options();

			if(isset($data['error'])) {
		
				echo $data['error'] . ': ' . $data['error_description'];
				die;
		
			}

			elseif(isset($_GET['code'])) {
			
				if($credentials['state'] == $_GET['state']) {

					// Get token so you can make API calls
					$credentials = $this->get_token($_GET);
				} else {
					// CSRF attack? Or did you mix up your states?
					die;
				}
	
			}

			else {

				if($credentials['expires_at'] < time()) {

					if($credentials['refresh_token'] != '') {

						$data = [
							'code' => $credentials['refresh_token']
						];

						$credentials = $this->get_refresh_token($data);

					} else {

						$credentials = $this->reset_db();

					}

				}

				if($credentials['access_token'] == '') {
			
					$this->get_authorization();
			
				}

			}

			return $credentials;

		}

		private function get_spotify_options() {

			$output['access_token'] 	= get_option('spotify_access_token');
			$output['refresh_token'] 	= get_option('spotify_refresh_token');
			$output['state'] 			= get_option('spotify_state');
			$output['expires_in'] 		= get_option('spotify_expires_in');
			$output['expires_at'] 		= get_option('spotify_expires_at');

			return $output;

		}

		private function get_refresh_token($data) {

			global $wp;
			$redirect_uri = home_url(add_query_arg(array(),$wp->request));

			$spotify_client_id = get_option('spotify_client_id');
			$spotify_client_secret = get_option('spotify_client_secret');

			$postdata = [
				'grant_type' 	=> 'refresh_token',
				'refresh_token' => $data['code'],
				'redirect_uri' 	=> $redirect_uri,
				'client_id' 	=> $spotify_client_id,
				'client_secret' => $spotify_client_secret
			];

			$token = $this->curl_get('https://accounts.spotify.com/api/token', true, 'post', $postdata);

			update_option( 'spotify_access_token', $token->access_token );
			update_option( 'spotify_expires_in', $token->expires_in );
			update_option( 'spotify_expires_at', time() + $token->expires_in );

			$new_credentials['access_token'] 	= $token->access_token;
			$new_credentials['expires_in'] 		= $token->expires_in;
			$new_credentials['expires_at'] 		= time() + $token->expires_in;
			
			return $new_credentials;

		}

		private function get_token($data) {

			global $wp;
			$redirect_uri = home_url(add_query_arg(array(),$wp->request));

			$spotify_client_id = get_option('spotify_client_id');
			$spotify_client_secret = get_option('spotify_client_secret');

			$postdata = [
				'grant_type' 	=> 'authorization_code',
				'code' 			=> $data['code'],
				'redirect_uri' 	=> $redirect_uri,
				'client_id' 	=> $spotify_client_id,
				'client_secret' => $spotify_client_secret
			];

			$token = $this->curl_get('https://accounts.spotify.com/api/token', true, 'post', $postdata);

			update_option( 'spotify_access_token', $token->access_token );
			update_option( 'spotify_refresh_token', $token->refresh_token );
			update_option( 'spotify_expires_in', $token->expires_in );
			update_option( 'spotify_expires_at', time() + $token->expires_in );

			$new_credentials['access_token'] 	= $token->access_token;
			$new_credentials['refresh_token'] 	= $token->refresh_token;
			$new_credentials['expires_in'] 		= $token->expires_in;
			$new_credentials['expires_at'] 		= time() + $token->expires_in;
			
			return $new_credentials;

		}

		private function get_authorization() {

			global $wp;
			$redirect_uri = home_url(add_query_arg(array(),$wp->request));

			$spotify_client_id = get_option('spotify_client_id');
			$spotify_client_secret = get_option('spotify_client_secret');



			$params = [
				'response_type' => 'code',
				'client_id' 	=> $spotify_client_id,
				'redirect_uri' 	=> $redirect_uri,
				'state' 		=> uniqid('', true),
				'scope' 		=> 'playlist-read-private user-library-read user-read-currently-playing'
			];

			$url = 'https://accounts.spotify.com/authorize?'.http_build_query($params);

			update_option('spotify_state', $params['state']);
			
			wp_redirect( $url );
			exit;			

		}

		private function get_no_of_albums($headers) {

			$output = [];

			$url = 'https://api.spotify.com/v1/me/albums';
			$output = $this->curl_get($url, $headers);

			return $output->total;

		}

		private function get_playlist_length($headers) {

			$output = [];

			$url = 'https://api.spotify.com/v1/users/amadore/playlists/6jP2cBhQHmEqxoCr4UMp03/tracks';

			$output = $this->curl_get($url, $headers);

			return $output->total;

		}

		private function reset_db() {

			update_option( 'spotify_access_token', '' );
			update_option( 'spotify_refresh_token', '' );
			update_option( 'spotify_state', '' );
			update_option( 'spotify_expires_in', 0 );
			update_option( 'spotify_expires_at', 0 );

		}

		public function get_albums() {

			if(!is_page('spotify-albums')) { return; }

			$credentials = $this->get_credentials($data);

			if($credentials['expires_at'] >= time() &&  $credentials['access_token'] != '') {

				$headers = 'Authorization: Bearer '.$credentials['access_token'];
				$no_of_albums = $this->get_no_of_albums($headers);

				for($i = 0; $i < $no_of_albums; $i += 20) {
					
					$url = 'https://api.spotify.com/v1/me/albums?market=SE&offset='.$i;
						
					$data = $this->curl_get($url, $headers);

					$spotify_posts = [];		

					foreach($data->items as $item) {

						$image = false;

						if(isset($item->album->images[0]->url) && is_object($item->album)) {
							$image = $item->album->images[0]->url;
						}

						$item_data = [
							'name' => $item->album->artists[0]->name.' - '.$item->album->name,
							'url' => $item->album->external_urls->spotify,
							'timestamp' => $item->added_at,
							'image' => $image,
							'tag' => 'spotify_album'
						];
						
						$spotify_posts[] = new Spotify($item_data);
						
					}

					// echo '<pre>';
					// 	print_r($spotify_posts);
					// echo '</pre>';

					// die;

					$spotify_posts = array_reverse($spotify_posts);

					$data = array(
						"numberposts" => -1,
						'meta_query' => array(
							array(
								'key' => 'source',
								'value' => 'spotify_album',
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

					foreach($spotify_posts as $key => $values) {

						$data = [];

						$post_exists = in_array($values->url, $current_urls) ? true : false;

						if(!$post_exists) {

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

							// 4. lägg in alla kategories id:n i post-data

							// spara ner post
							$data = array(
								"post_title" 		=> $values->name,
								"post_category"		=> $post_category_ids,
								"post_date" 		=> date("Y-m-d", $values->timestamp)."T".date("H:i:s", $values->timestamp),
								"post_status" 		=> "publish",
								"tags_input" 		=> array('Pocket')
							);

							$saved_post_id = wp_insert_post($data);

							add_post_meta($saved_post_id, "source", "spotify_album");
							add_post_meta($saved_post_id, "external_url", $values->url);

							if($values->image != '' && @GetImageSize($values->image)) {

								add_post_meta($saved_post_id, "external_image", $values->image);

								$filename = explode('/', $values->image);
								$filename = array_reverse($filename);
								//$filename = explode('?', $filename[0]);
								$filename = $filename[0].'.jpg';

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

				}

			}

		}

		public function get_favourites() {

			if(!is_page('spotify-favourites')) { return; }

			$credentials = $this->get_credentials($data);

			if($credentials['expires_at'] >= time() &&  $credentials['access_token'] != '') {

				$headers = 'Authorization: Bearer '.$credentials['access_token'];
				$playlist_length = $this->get_playlist_length($headers);

				for($i = 0; $i < $playlist_length; $i += 100) {
					
					$url = 'https://api.spotify.com/v1/users/amadore/playlists/6jP2cBhQHmEqxoCr4UMp03/tracks?offset='.$i;
						
					$data = $this->curl_get($url, $headers);

					$spotify_posts = [];		

					foreach($data->items as $item) {

						if(isset($item->track->album->images[0]->url) && is_object($item->track->album)) {
							$image = $item->track->album->images[0]->url;
						}

						$item_data = [
							'name' => $item->track->artists[0]->name.' - '.$item->track->name,
							'url' => $item->track->external_urls->spotify,
							'timestamp' => $item->added_at,
							'image' => $image,
							'tag' => 'spotify_favourite'
						];
						
						$spotify_posts[] = new Spotify($item_data);
						
					}

					$spotify_posts = array_reverse($spotify_posts);

					$data = array(
						"numberposts" => -1,
						'meta_query' => array(
							array(
								'key' => 'source',
								'value' => 'spotify_favourite',
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

					foreach($spotify_posts as $key => $values) {

						$data = [];

						$post_exists = in_array($values->url, $current_urls) ? true : false;

						if(!$post_exists) {

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

							// 4. lägg in alla kategories id:n i post-data

							// spara ner post
							$data = array(
								"post_title" 		=> $values->name,
								"post_category"		=> $post_category_ids,
								"post_date" 		=> date("Y-m-d", $values->timestamp)."T".date("H:i:s", $values->timestamp),
								"post_status" 		=> "publish",
								"tags_input" 		=> array('Pocket')
							);

							$saved_post_id = wp_insert_post($data);

							add_post_meta($saved_post_id, "source", "spotify_album");
							add_post_meta($saved_post_id, "external_url", $values->url);

							if($values->image != '' && @GetImageSize($values->image)) {

								add_post_meta($saved_post_id, "external_image", $values->image);

								$filename = explode('/', $values->image);
								$filename = array_reverse($filename);
								//$filename = explode('?', $filename[0]);
								$filename = $filename[0].'.jpg';

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

				}

			}

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

	}