<?php

require_once('createHTML.php');
require_once('createIndexFiles.php');

class folderFilesReader {

  protected $language = 'english'; // english | spanish
  protected $main_folder = '/Applications/MAMP/htdocs/bomc/original';
  protected $main_folder_2 = '/%language%/scriptures'; //'/web/sites/vektr/www/dev/bomc/scriptures'
  protected $path_source = '';
  protected $path_target = '';
  protected $scriptures_folder = '/original/';
  protected $scriptures_modified_folder = '/fixed_%language%/';
  protected $path_mainfolder = 7; // 
  protected $cmd = ''; // media | content | summary | html
  protected $only_books = array('ot', 'nt', 'bofm', 'dc-testament', 'pgp');
  // protected $do_not_include_chapters = array('illustrations.html','chron-order.html','pronunciation.html');
  protected $files_to_ignore = array('links.html', 'library.html', '.DS_Store', 'index.html'
      , 'illustrations.html', 'chron-order.html'
          // ,'pronunciation.html'
  );
  protected $current_folder;
  protected $current_file;
  protected $current_file_id;
  protected $new_line = "\n";

  function __construct() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $this->path_mainfolder_book = $this->path_mainfolder + 1;
    $this->cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : 'summary';
    $this->language = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'english';
    $this->scriptures_modified_folder = str_replace('%language%', $this->language
            , $this->scriptures_modified_folder);
    $this->path_source = str_replace('%language%', $this->language
            , $this->main_folder . $this->main_folder_2);
    $this->path_target = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder
            , $this->path_source);
  }

  protected function removeNewHtmlFolder() {
    $folder_to_remove = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder
            , $this->main_folder . '/');
    $this->rrmdir($folder_to_remove);
    $this->msgProgress('Folder <b>' . $folder_to_remove . '</b> removed.');
  }

  private function rrmdir($dir) {
    if (is_dir($dir)) {
      // echo '<span style="color:blue;font-size:70%">'.'--'.$dir.'</span><br/>';
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir . "/" . $object) == "dir")
            $this->rrmdir($dir . "/" . $object);
          else
            unlink($dir . "/" . $object);
        }
      }
      reset($objects);
      rmdir($dir);
      // echo '<span style="color:blue;font-size:70%">'.'-'.$dir.'</span><br/>';
    }
  }

  protected function createNewFolder($newHTMLFile) {
    $folders = explode('/', $newHTMLFile);
    unset($folders[count($folders) - 1]);
    $this->createDir(implode('/', $folders));
  }

  protected function createDir($dir_name) {
    if (!file_exists($dir_name)) {
      mkdir($dir_name, 0777, true);
    }
  }

  protected function msgProgress($text) {
    echo '<div style="border:2px solid Violet;">' . $text . '</div>';
    flush();
  }

  protected function readFolderAndFiles($content) {
    $this->msgProgress('Reading Folders and its files.');
    foreach ($content as $idx => $source) {
      $folders = array_filter(explode('/', $source));
      if ($this->ignoreFile($source)) {
        // Do nothign with this record
      } else if (( $this->cmd == 'media' && strpos($source, '_manifest.html') > 0 ) // Get Media from _manifest.html files.
              || ($this->cmd != 'media' && in_array($folders[$this->path_mainfolder_book], $this->only_books) ) // Only specific books
      ) {
        $this->getFolderFile($folders);
        $this->processFile($source);
      }
    }
    switch ($this->cmd) {
      case 'media':
        $this->msgProgress('Media Extracted.');
        break;
      case 'content':
        $this->msgProgress('Extract Content.');
        break;
      case 'html':
        $this->msgProgress('HTML files replicated and cleaned up');
        break;
      default:
        $this->msgProgress('Parting Meta Info');
        break;
    }
  }

  protected function ignoreFile($source) {
    forEach ($this->files_to_ignore as $file) {
      if (strpos($source, $file)) {
        // echo 'File Ignored '.$source.'<br/>';
        return TRUE;
      }
    }
    return FALSE;
  }

  private function getFolderFile($folders) {
    $this->current_folder = $folders[$this->path_mainfolder_book];
    $this->current_file = $folders[count($folders)];
    if (count($folders) >= $this->path_mainfolder_book + 2) {
      $this->current_file_id = $folders[count($folders) - 1] . '/' . $folders[count($folders)];
    } else {
      $this->current_file_id = $folders[count($folders)];
    }
    $new_folders = $folders;
    $new_folders[count($folders)] = '';
  }

  protected function downloadAndSaveFile($source, $file, $into_folder) {
    $downloaded_stream = file_get_contents($source);
    $this->createDir($into_folder);
    $into_file = $into_folder . '/' . $file;
    file_put_contents($into_file, $downloaded_stream);
  }

  protected function modifyTagString($string_from, $string_to, $file_stream, $replace_with = '') {
    $from = strpos($file_stream, $string_from);
    if ($from === false) {
      // throw new Exception('ERROR on ' . $string_from . ' not found');
      // return;
      return $file_stream;
    } else {
      $to = strpos($file_stream, $string_to);
      if ($to === false) {
        throw new Exception('ERROR on ' . $string_to . ' not found');
        return;
      } else {
        $to += strlen($string_to);
        $string_to_remove = substr($file_stream, $from, $to - $from);
        $file_stream = str_replace($string_to_remove, $replace_with, $file_stream);
      }
      return $file_stream;
    }
  }

  protected function insertString($string_search, $file_stream, $insert_string) {
    $from = strpos($file_stream, $string_search);
    if ($from === false) {
      // throw new Exception('ERROR on ' . $string_from . ' not found');
      // return;
      return $file_stream;
    } else {
      // $to = strpos($file_stream, $string_to);
      $to = $from + strlen($string_search);
      if ($to === false) {
        throw new Exception('ERROR on ' . $string_to . ' not found');
        return;
      } else {
        $to += strlen($insert_string);
        $file_stream = str_replace($string_search
                                  , $string_search.$insert_string
                                  , $file_stream);
      }
      return $file_stream;
    }
  }

  protected function getArrayFromNode($node) {
    $array = false;
    if ($node->hasAttributes()) {
      foreach ($node->attributes as $attr) {
        $array[$attr->nodeName] = $attr->nodeValue;
      }
    }
    if ($node->hasChildNodes()) {
      if ($node->childNodes->length == 1) {
        $array[$node->firstChild->nodeName] = $node->firstChild->nodeValue;
      } else {
        foreach ($node->childNodes as $childNode) {
          if ($childNode->nodeType != XML_TEXT_NODE) {
            $array[$childNode->nodeName][] = $this->getArrayFromNode($childNode);
          }
        }
      }
    }
    return $array;
  }

  protected function printArray($array, $level) {
    echo '<pre>';
    foreach ($array as $idx => $key) {
      if (is_array($key)) {
        $level++;
        $this->printArray($key, $level);
        $level--;
      } else {
        echo $this->identation($level) . $idx . '=' . $key . '<br/>';
      }
    }
    echo '</pre>';
  }

  protected function identation($level) {
    $levelStr = '';
    if ($level != 0) {
      for ($i = 1; $i <= $level; $i++) {
        $levelStr .= '&nbsp;&nbsp;';
      }
    }
    return $levelStr;
  }

  protected function array2ul($array, $name_array = '') {
    $uls = '<ul style="padding-left:1%; color:Blue;">';
    $ule = '</ul>';
    $lis = '<li style="padding-left:1%;">';
    $lie = '</li>';
    // Start
    echo $uls;
    $counter = 0;
    foreach ($array as $idx => $key) {
      $counter++;
      if (is_array($key)) {
        echo $lis . $idx . $lie;
        echo $uls;
        $this->array2ul($key);
        echo $ule;
      } else {
        echo $lis . $idx . '[<b style="color:Tomato;">' . $key . '</b>]' . $lie;
      }
    }
    echo $ule;
    echo '<sup>count for this array:' . $counter . '</sup><hr />';
    flush();
  }

}

?>