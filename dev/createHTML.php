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
    /*echo 'href: '.$href.'<br>';
    echo 'name: '.$name.'<br>';
    echo 'mode: '.$mode.'<br>';*/
    if ($href === 'enos/index.html') {
      $href = 'enos/1.html';
      $mode = 1;
    } else if ($href === 'jarom/index.html') {
      $href = 'jarom/1.html';
      $mode = 1;
    } else if ($href === 'omni/index.html') {
      $href = 'omni/1.html';
      $mode = 1;
    } else if ($href === 'w-of-m/index.html') {
      $href = 'w-of-m/1.html';
      $mode = 1;
    } else if ($href === '4-ne/index.html') {
      $href = '4-ne/1.html';
      $mode = 1;
    } else if ($href === 'index_pronunciation.html') {
      $href = 'pronunciation.html';
      $mode = 1;
    } else if ($href === 'philem/index.html') {
      $href = 'philem/1.html';
      $mode = 1;
    } else if ($href === '2-jn/index.html') {
      $href = '2-jn/1.html';
      $mode = 1;
    } else if ($href === '3-jn/index.html') {
      $href = '3-jn/1.html';
      $mode = 1;
    } else if ($href === 'jude/index.html') {
      $href = 'jude/1.html';
      $mode = 1;
    } else if ($href === 'obad/index.html') {
      $href = 'obad/1.html';
      $mode = 1;
    } else if ($href === 'js-m/index.html') {
      $href = 'js-m/1.html';
      $mode = 1;
    } else if ($href === 'js-h/index.html') {
      $href = 'js-h/1.html';
      $mode = 1;
    } else if ($href === 'a-of-f/index.html') {
      $href = 'a-of-f/1.html';
      $mode = 1;
    }
    $href = sprintf('<a href="#" onclick="openLink(\'%s\', \'%s\')"><p class="title" data-aid="" id="title2">%s</p></a>' . $this->new_line, $href, $mode, $name);
    $this->setContent($href);
  }

  public function createFile() {
    file_put_contents($this->file_name, $this->html);
  }

}

?>