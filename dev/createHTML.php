<?php

class createHTML {

  private $html = '';
  private $file_name = '';
  private $new_line = "\n";

  function __construct() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $this->html = '<!DOCTYPE html><html>';
  }

  public function setFileName($file_name) {
    $this->file_name = $file_name;
  }

  public function getFileName() {
    return $this->file_name;
  }

  public function setHead($title = 'Scriptures', $parent_folder = '../../') {
    $this->html .= '<head>'
            . '<title>' . $title . '</title>' . $this->new_line
            . '<meta charset="UTF-8"/>' . $this->new_line
            // . '<meta name="viewport" content="width=device-width, initial-scale=1">' . $this->new_line
            . '<link rel="stylesheet" href="' . $parent_folder . 'styles/navigation.css?' . date("YmdHis") . '">' . $this->new_line
            // . '<link rel="stylesheet" href="' . $parent_folder . 'styles/scriptures.css?'. date("YmdHis").'">' . $this->new_line
            . '<link rel="stylesheet" href="' . $parent_folder . 'styles/responsive.css?' . date("YmdHis") . '">' . $this->new_line
            . '<link rel="stylesheet" href="' . $parent_folder . 'styles/animate.css?' . date("YmdHis") . '">' . $this->new_line
            . '<script type="text/javascript" src="' . $parent_folder . 'js/hammer.min.js"></script>' . $this->new_line
            . '</head><body>' . $this->new_line
    ;
  }

  public function setFooter($parent_folder = '../../') {
    $this->html .= '<script type="text/javascript" src="' . $parent_folder . 'js/functions_index.js"></script></body></html>';
  }

  public function setContent($content) {
    $this->html .= $content;
  }

  public function setHref($href, $name, $mode) {
    $href = sprintf('<a href="#" onclick="openLink(\'%s\', \'%s\')"><p class="title" data-aid="" id="title2">%s</p></a>' . $this->new_line, $href, $mode, $name);
    $this->setContent($href);
  }

  public function createFile() {
    file_put_contents($this->file_name, $this->html);
  }

}

?>