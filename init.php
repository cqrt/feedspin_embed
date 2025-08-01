<?php
class feedspin_embed extends Plugin {
    private $host;

    public function init($host) {
        $this->host = $host;
        $host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
    }

    public function about() {
        return [
            1.1,
            "Embed original articles including fullscreen video",
            "cqrt",
            true
        ];
    }

    public function get_js() {
        $js_path = dirname(__FILE__) . "/embed.js";
        return file_exists($js_path) ? file_get_contents($js_path) : "console.error('feedspin_embed: JS file missing')";
    }

    public function get_css() {
        $css_path = dirname(__FILE__) . "/embed.css";
        return file_exists($css_path) ? file_get_contents($css_path) : "/* feedspin_embed: CSS file missing */";
    }

    public function hook_article_button($line) {
        $id = (int)$line['id'];
        $title = __('Embed Article'); // Correct translation method
        
        return <<<HTML
            <i class='material-icons' 
               onclick="embedOriginalArticle({$id})"
               style='cursor:pointer'
               title='{$title}'>
                zoom_out_map
            </i>
        HTML;
    }
    
    public function getUrl() {
        if (!isset($_REQUEST['id'])) {
            $this->return_error("Missing article ID", 400);
        }

        $id = (int)$_REQUEST['id'];
        if ($id <= 0) {
            $this->return_error("Invalid article ID", 400);
        }

        $owner_uid = $_SESSION['uid'] ?? null;
        if (!$owner_uid) {
            $this->return_error("Authentication required", 401);
        }

        try {
            $sth = $this->pdo->prepare(
                "SELECT ttrss_entries.link 
                 FROM ttrss_entries
                 INNER JOIN ttrss_user_entries 
                    ON ttrss_entries.id = ttrss_user_entries.ref_id
                 WHERE ttrss_entries.id = :id 
                    AND ttrss_user_entries.owner_uid = :uid
                 LIMIT 1"
            );
            
            $sth->execute([':id' => $id, ':uid' => $owner_uid]);
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['link'])) {
                $this->return_error("Article not found", 404);
            }

            $url = $row['link'];
            $embed_url = $this->generate_embed_url($url);
            
            header('Content-Type: application/json');
            echo json_encode([
                "url" => $embed_url,
                "id" => $id
            ]);
            
        } catch (PDOException $e) {
            error_log("Feedspin Embed DB Error: " . $e->getMessage());
            $this->return_error("Database error", 500);
        }
    }
    
    private function generate_embed_url($url) {
        $yt_patterns = [
            '~youtube\.com/watch\?.*v=([\w-]{11})~',
            '~youtube\.com/shorts/([\w-]{11})~',
            '~youtu\.be/([\w-]{11})~'
        ];
        
        $vimeo_pattern = '~vimeo\.com/(\d+)~';
        
        foreach ($yt_patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return "https://www.youtube-nocookie.com/embed/{$matches[1]}?autoplay=1";
            }
        }
        
        if (preg_match($vimeo_pattern, $url, $matches)) {
            return "https://player.vimeo.com/video/{$matches[1]}?autoplay=1";
        }
        
        return $url;
    }
    
    private function return_error($message, $http_code = 400) {
        http_response_code($http_code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    public function api_version() {
        return 2;
    }
}
