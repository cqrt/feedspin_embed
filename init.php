<?php
class feedspin_embed extends Plugin {
  private $host;

	function init($host) {
		$this->host = $host;

		$host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
	}

	function about() {
		return array(1.0,
			"Embed original articles including fullscreen video",
			"cqrt");
	}

	function get_js() {
		return file_get_contents(dirname(__FILE__) . "/embed.js");
	}

	function get_css() {
		return file_get_contents(dirname(__FILE__) . "/embed.css");
	}

	function hook_article_button($line) {
		$id = $line["id"];

		$rv = "<img class='embedIcon' style=\"cursor : pointer\"
			onclick=\"embedOriginalArticle($id)\"
			title='".__('Toggle embed original')."'>";

		return $rv;
	}
	
	function getUrl() {
		$id = db_escape_string($_REQUEST['id']);

		$result = db_query("SELECT link
				FROM ttrss_entries, ttrss_user_entries
				WHERE id = '$id' AND ref_id = id AND owner_uid = " .$_SESSION['uid']);

		$url = "";

		if (db_num_rows($result) != 0) {
			$url = db_fetch_result($result, 0, "link");

		}
		if (strpos($url, 'youtube') !== FALSE) {
			$capture = '/(.*?)(?:href="https?:\/\/)?(?:www\.)?(?:youtube\.com(?:\/watch?.*?v=))([\w\-]{10,12}).*$/';
			$output = 'http://www.youtube.com/embed/$2?autoplay=1&iv_load_policy=3&modestbranding=1';
			$url = preg_replace($capture, $output, $url);
		} else 
		if (strpos($url, 'vimeo') !== FALSE) {
			$capture = '/https?:\/\/(?:www\.)?vimeo.com\/(?:channels\/([^\/]*)\/|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/';
			$output = 'http://player.vimeo.com/video/$4?autoplay=1';
			$url = preg_replace($capture, $output, $url);
		}
		print json_encode(array("url" => $url, "id" => $id));
	}
	
	function api_version() {
		return 2;
	}

}
?>
