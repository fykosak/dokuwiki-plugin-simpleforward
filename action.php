<?php

/**
 * DokuWiki Plugin simpleforward (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michal Koutný <michal@fykos.cz>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC'))
    die();

class action_plugin_simpleforward extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handle_dokuwiki_started');
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_dokuwiki_started(Doku_Event &$event, $param) {
        global $INFO;
        global $ID;
        global $conf;
        $disableForward =
                !$this->getConf('enabled') /* Disabled by user */ || $_GET['dokuwiki_simpleforward'] === '0' /* Disabled for the request */ || ($this->nonemptyPath() && $ID === $conf['start']); /* Default page is not forwarded due to form actions */

        if (!$INFO['exists'] && !$disableForward) {
            $basedir = $this->getConf('document_root');
            $index = $basedir . DIRECTORY_SEPARATOR . $this->getConf('index');
            if (!$basedir || !file_exists($index)) {
                return;
            }

            $ru = $_SERVER['REQUEST_URI'];
            $url = "http://dummy.org$ru";
            $urlParts = parse_url($url);
            $path = $urlParts['path'];
            $file = $basedir . DIRECTORY_SEPARATOR . $path;
            unset($_GET['id']);

            if (is_file($file)) {
                if (strtolower(substr($path, -4)) == '.php') {
                    $this->forward_request($file);
                } else {
                    $this->send_file($file);
                }
            } else {
                $this->forward_request($index);
            }
        }
    }

    private function send_file($file) {
        static $content = array(
    'css' => 'text/css',
        );
        $ext = strtolower(substr($file, strrpos($file, '.') + 1));
        if (isset($content[$ext])) {
            $type = $content[$ext];
        } else {
            $type = mime_content_type($file);
        }

        header('Content-Type: ' . $type);
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }

    private function forward_request($file) {
        @session_write_close();
        require $file;
        exit;
    }

    private function nonemptyPath() {
        $parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        return $parts[0] !== DOKU_BASE;
    }

}

// vim:ts=4:sw=4:et:
