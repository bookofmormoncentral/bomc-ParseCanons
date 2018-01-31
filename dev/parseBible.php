<?php
/*
  http://localhost:8888/bomc/dev/parseBible.php?cmd=media&lang=english
  http://localhost:8888/bomc/dev/parseBible.php?cmd=content

  cmd = media | content | summary | default=summary
  lang= spanish | portuguese = english | default = english

  To create new HTML:
  http://localhost:8888/bomc/dev/parseBible.php?cmd=html&lang=english
  and it creates:
  http://localhost:8888/bomc/fixed_html/english/scriptures/
 */
require_once('folderFilesReader.php');

class parseBible extends folderFilesReader {

  private $href = array();
  private $meta = array();
  private $printedTitle = '';
  private $books_sub = array();
  private $allKeys = array();

  function __construct() {
    parent::__construct();
  }

  public function execute() {
    $this->htmlHeader();
    $content = ($this->scanDirectories($this->path_source));
    $this->reorgBooksArray();
    //debug
    // $jmh=0;
    // foreach($this->books_sub as $b => $sub) {
    //   $jmh++;
    //   echo $jmh.' -- '.$b.'<br/>';
    // }
    // $this->array2ul($this->books_sub);
    //debug-end
    if ($this->cmd == 'html') {
      $cif = new createIndexFiles();
      $cif->removeNewHtmlFolder();
    }
    $this->readFolderAndFiles($content);
    switch ($this->cmd) {
      case 'summary':
        $this->debugMeta();
      case 'html':
        $cif->books_sub = $this->books_sub;
        $cif->execute();
      default:
        # code...
        break;
    }
    //debug
    // $this->keysUsedInFile();
    // $this->array2ul($this->allKeys);
    $this->htmlFooter();
  }

  /*
    Only those books allowed, and in the right order.
   */

  private function reorgBooksArray() {
    $this->msgProgress('Array Reorg...');
    $reorg = $this->books_sub;
    // $this->books_sub = array();
    $books_nr = count($this->only_books);
    // Read the first only
    foreach ($reorg as $collection => $books) {
      $split = explode('/', $collection);
      if (end($split) == 'scriptures') {
        $collection_books = array();
        foreach ($books as $ii => $book) {
          if (in_array($book, $this->only_books)) {
            $collection_books[array_search($book, $this->only_books)] = $book;
            $this->reorgSubBooksArray($collection . '/' . $book);
          }
        }
        ksort($collection_books);
        $this->books_sub[$collection] = $collection_books;
      }
      break; // only the first entry, with the library
    }
  }

  private function reorgSubBooksArray($index) {
    // echo '$index '.$index.'<br/>';
    // echo '<pre>';
    // var_export($this->books_sub[$index]);
    // echo '</pre>';
    // foreach($this->books_sub[$index] as $i=>$book) {
    // }
  }

  /*
    Also, make sure these are included in
    files[] = array('https://edge.ldscdn.org/cdn2/csp/ldsorg/css/pages/scriptures.css', 'scriptures.css');
    createIndexFiles.php->constructCSSFiles()
   */

  private function keysUsedInFile() {
    $files = array();
    $new_styles = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder
            , $this->main_folder . '/styles');
    // files copied inside: createIndexFiles->constructCSSFiles();
    // $files[] = $new_styles . '/content.css';
    // $files[] = $new_styles . '/scriptures.css';
    // $files[]=$new_styles.'/lds-old.css';
    echo '<ul>';
    foreach ($this->allKeys as $k => $v) {
      echo '<li style="color:red;">' . $k . '</li>' . '<ul>';
      foreach ($files as $file) {
        $folders = explode('/', $file);
        echo '<li style="color:blue;font-size: 75%">' . $this->identation(2) . end($folders) . '</li>';
        $handle = fopen($file, "r");
        if ($handle) {
          while (($line = fgets($handle)) !== false) {
            if (strpos($line, $k) !== false) {
              echo '<span style="font-size: 90%">' . $this->identation(4) . $line . '</span><br />';
            }
          }
          flush();
          fclose($handle);
        } else {
          // error opening the file.
        }
      }
      echo '</ul>';
    }
    echo '</ul>';
  }

  /*
   */

  protected function processFile($source) {
    $dom = new DOMDocument();
    $dom->load($source);
    switch ($this->cmd) {
      case 'media':
        $this->parseMultiMediaFromMeta($dom);
        break;
      case 'content':
        $this->parseHtmlBody($dom, 'head');
        $this->parseHtmlBody($dom, 'body');
        break;
      case 'html':
        $this->replicateHTMLFile($source, $dom);
        break;
      default:
        $this->parseMetaInfo($dom);
        break;
    }
  }

  /*
   * Get MultiMediaFile
   */

  private function parseMultiMediaFromMeta($dom) {
    foreach ($dom->getElementsByTagNameNS('http://www.lds.org/schema/lds-meta/v1', '*') as $element) {
      if ($element->localName == 'title') {

        if ($type == 'navigation') {
          $title = '<h4>' . $element->nodeValue . '</h4>';
          $this->printedTitle = FALSE;
        }
      } else if ($element->localName == 'pdf') {
        $this->printTitle($title);
        echo '<a href="' . $element->getAttribute('src') . '" target="_blank">' . $element->getAttribute('src') . '</a><br/>';
      } else if ($element->localName == 'audio') {
        if ($element->hasChildNodes()) {
          foreach ($element->childNodes as $childNode) {
            if ($childNode->localName == 'source') {
              $attrs = $this->getArrayFromNode($childNode);
              $this->printTitle($title);
              echo $attrs['type'] . ': <a href="' . $attrs['src']
              . '" target="_blank">' . $attrs['src'] . '</a><br/>';
            }
          }
        }
      } else if ($element->localName == 'source') {
        $this->printTitle($title);
        echo '<a href="' . $element->getAttribute('src') . '" target="_blank">' . $element->getAttribute('src') . '</a><br/>';
      }
    }
  }

  private function parseHtmlBody($dom, $tag) {
    $array = array();
    foreach ($dom->getElementsByTagName($tag) as $node) {
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
    }
    echo '<h4>' . $tag . '</h4>';
    $this->printArray($array, 0);
  }

  /*
    Create True HTML file from the original XSL files:
    1) Clean up HTML extra content.
    2) Insert TRUE HTML5
    3) Add CSS.
   */

  private function replicateHTMLFile($source, $dom) {
    $file_stream = $this->cleanUpHtml($source, file_get_contents($source));
    $newHTMLFile = str_replace($this->scriptures_folder, $this->scriptures_modified_folder, $source);
    $this->createNewFolder($newHTMLFile);
    file_put_contents($newHTMLFile, $file_stream);
  }

  private function cleanUpHtml($source, $file_stream_content) {
    // insert root div
    $file_stream = str_replace('<body>', '<body><div id="root">', $file_stream_content);
    // remove XML tag
    $file_stream = $this->modifyTagString('<?xml', '>', $file_stream);
    // make it HTML 5
    $file_stream = $this->modifyTagString('<html', '>', $file_stream, '<!DOCTYPE html><html>');
    // Remove <lds:meta>, and replace with CSS styles.
    $parent_folder = count(explode('/', $source)) == 10 ? '../../../' : '../../../../';
    $file_stream = $this->modifyTagString('<lds:meta', '</lds:meta>', $file_stream, $this->newCSS($source));
    // remove <footer>
    $file_stream = $this->modifyTagString('<footer class="study-notes">', '</footer>', $file_stream
            , '<footer class="study-notes"></footer></div><script type="text/javascript" src="' . $parent_folder . 'js/functions.js"></script>');
    // Remove study-note-ref and marker
    // echo $source.'<br/>';
    $file_stream = $this->removeAllStudyNotes($source, $file_stream);
    $this->xtractKeysFromFile($file_stream, 'class');
    return $file_stream;
  }

  private function xtractKeysFromFile($file_stream, $key, $start = 0) {
    $needle = $key . '="';
    $from = strpos($file_stream, $needle, $start);
    if ($from !== false) {
      $from = strpos($file_stream, '"', $from) + 1;
      $to = strpos($file_stream, '"', $from);
      if ($to !== false) {
        $length = ($to - $from);
        $keyValue = substr($file_stream, $from, $length);
        $this->allKeys[$keyValue] = '';
        // echo ''.$from . ' - '.$to . ' = '. $length. ' => '.$keyValue.'<br/>';
        $start = strrpos($file_stream, '<', $from);
        $end = strpos($file_stream, ' ', $start);
        // echo ''.$from . ' - ' . $to . ' = '. $length. ' => '.$keyValue
        //       . ' tag: ' . '$start [' . $start. '] ' . ' $end ['. $end .']'
        //       .'<br/>';
        $this->xtractKeysFromFile($file_stream, 'class', $to);
      }
    }
  }

  private function newCSS($source) {
    // include CSS
    $parent_folder = count(explode('/', $source)) == 10 ? '../../../' : '../../../../';
    $css = // '<link rel="stylesheet" href="'.$parent_folder.'styles/content.css">'
            // .
            '<link rel="stylesheet" href="' . $parent_folder . 'styles/scriptures.css">'. $this->new_line
            . '<link rel="stylesheet" href="' . $parent_folder . 'styles/animate.css">'. $this->new_line
            . '<script type="text/javascript" src="' . $parent_folder . 'js/hammer.min.js"></script>'. $this->new_line
    // .
    // '<link rel="stylesheet" href="'.$parent_folder.'styles/lds-old.css">'
    // .
    // '<link rel="stylesheet" href="' . $parent_folder . 'styles/googleOpenSans.css">'
    ;
    return $css;
  }

  private function removeAllStudyNotes($source, $file_stream) {
    $note_tag['a_open'] = '<a class="study-note-ref"';
    $note_tag['a_close'] = '</a>';
    $note_tag['sup_open'] = '<sup class="marker">';
    $note_tag['sup_close'] = '</sup>';
    $note_tag['tag_close'] = '>';
    $counter = 0;
    while (strpos($file_stream, $note_tag['a_open'])) {
      // if($source == '/Applications/MAMP/htdocs/bomc/original/english/scriptures/nt/john/1.html') {
      // echo $counter++ .'<br/>';
      // }
      $file_stream = $this->removeRefSudyNoteNMarker($source, $file_stream, $note_tag);
    }
    return $file_stream;
  }

  /*
    Remove study Notes and markers from a phrase or word.
    from this format:
    <a class="study-note-ref" href="#note4a">
    <sup class="marker">
    a
    </sup>
    text with note
    </a>
    to have only the phrase:
    text with note
   */

  private function removeRefSudyNoteNMarker($source, $file_stream, $note_tag) {
    $tag_a_from = strpos($file_stream, $note_tag['a_open']);
    if ($tag_a_from !== false) {
      $tag_a_to = strpos($file_stream, $note_tag['a_close'], $tag_a_from);
      if ($tag_a_to !== false) {
        $tag_sup_from = strpos($file_stream, $note_tag['sup_open'], $tag_a_from);
        // if($source == '/Applications/MAMP/htdocs/bomc/original/english/scriptures/nt/john/1.html') {
        //   echo '<div style="border:2px solid Black;">'
        //   .'tag_a_from '.$tag_a_from.'<br/>'
        //   .'tag_a_to '.$tag_a_to.'<br/>'
        //   .'tag_sup_from '.$tag_sup_from.'<br/>'
        //   .'</div>'
        //   ;
        // }
        if ($tag_sup_from !== false && $tag_sup_from < $tag_a_to) {
          $tag_sup_to = strpos($file_stream, $note_tag['sup_close'], $tag_a_from);
          if ($tag_sup_to !== false) {
            $from = $tag_a_from;
            $length = $tag_a_to - $tag_a_from + strlen($note_tag['a_close']);
            $string_to_remove = substr($file_stream, $from, $length);

            $phrase_from = $tag_sup_to + strlen($note_tag['sup_close']);
            $word_length = $tag_a_to - $phrase_from;
            $replace_with = substr($file_stream, $phrase_from, $word_length);

            $file_stream = str_replace($string_to_remove, $replace_with, $file_stream);
            // if($source == '/Applications/MAMP/htdocs/bomc/original/english/scriptures/bofm/1-ne/1.html') {
            // echo 
            // '<b>'.__FUNCTION__.'</b><br/>'
            // .'$tag_a_from['.$tag_a_from.']<br/>'
            // .'$tag_a_to['.$tag_a_to.']<br/>'
            // .'$tag_sup_from['.$tag_sup_from.']<br/>'
            // .'$tag_sup_to['.$tag_sup_to.']<br/>'
            // .'$from['.$from.']<br/>'
            // .'$length['.$length.']<br/>'
            // .
            // '<div style="color:red">'.htmlentities($string_to_remove) .'</div>'
            // .'$phrase_from['.$phrase_from.']<br/>'
            // .'$word_length['.$word_length.']<br/>'
            // .'<div style="color:blue">'.htmlentities($replace_with) .'</div><hr />';
            // }
          } else {
            throw new Exception('ERROR on </sup> not found in ' . $source . ' ~~~~~ ');
          }
        } else {
          // <sup> tag not found. This means, no special note, and still has to be cleaned up.
          // throw new Exception('ERROR on <sup> not found in '.$source .' ~~~~~ ');
          $from = $tag_a_from;
          $length = $tag_a_to - $tag_a_from + strlen($note_tag['a_close']);
          $string_to_remove = substr($file_stream, $from, $length);

          $phrase_from = strpos($file_stream, $note_tag['tag_close'], $tag_a_from) + 1;
          $word_length = $tag_a_to - $phrase_from;
          $replace_with = substr($file_stream, $phrase_from, $word_length);

          $file_stream = str_replace($string_to_remove, $replace_with, $file_stream);
          // if($source == '/Applications/MAMP/htdocs/bomc/original/english/scriptures/bofm/1-ne/1.html') {
          //   echo $source.'<br/>'.
          //   '<b>'.__FUNCTION__.'() with no '.htmlentities('<sup>').'</b><br/>'
          //   .'$tag_a_from['.$tag_a_from.']<br/>'
          //   .'$tag_a_to['.$tag_a_to.']<br/>'
          //   .'$from['.$from.']<br/>'
          //   .'$length['.$length.']<br/>'
          //   .
          //   '<div style="color:red">'.htmlentities($string_to_remove) .'</div>'
          //   .'$phrase_from['.$phrase_from.']<br/>'
          //   .'$word_length['.$word_length.']<br/>'
          //   .'<div style="color:blue">'.htmlentities($replace_with) .'</div><hr />';
          // }
        }
      } else {
        throw new Exception('ERROR on </a> not found in ' . $source . ' ~~~~~ ');
      }
    }
    return $file_stream;
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
        $to += strlen($string_to);
        // $string_to_remove = substr($file_stream, $from, $to - $from);
        $file_stream = str_replace($string_search, $string_search+$insert_string, $file_stream);
      }
      return $file_stream;
    }
  }

  private function parseMetaInfo($dom) {
    foreach ($dom->getElementsByTagNameNS('http://www.lds.org/schema/lds-meta/v1', '*') as $element) {
      if ($element->localName == 'meta') {
        $this->meta[$this->current_folder][$this->current_file_id]['meta'] = array();
      } else {
//        echo '---<br/>$element->localName: '.$element->localName.'<br/>';
//        if ($element->hasChildNodes()) {
//          $this->previous_node_parent=$element->localName;
//        } else {
//          $this->previous_node_parent='';
//        }
        $this->getNodeAttrs($element);
      }
    }
  }

  /*
   * Get Media Resources from Meta
   */

  //  private function getMediaFromMetaCHALE($dom) {
  //    foreach ($dom->getElementsByTagNameNS('http://www.lds.org/schema/lds-meta/v1', '*') as $element) {
  //      if ($element->localName == 'meta') {
  //        $this->meta[$this->current_folder][$this->current_file_id]['meta'] = array(
  //            'title_file' => ''
  //            , 'title_citation' => ''
  //            , 'title_short-citation' => ''
  //            , 'title_citation_name' => ''
  //            , 'title_navigation' => ''
  //        );
  //      } else {
  //        $parent_node = $element->localName;
  //        $value = $element->nodeValue;
  //        if ($element->hasAttributes()) {
  //          foreach ($element->attributes as $attr) {
  //            $nodeName = '';
  //            $type = $element->getAttribute('type');
  //            $itemtype = $element->getAttribute('itemtype');
  //            if ($itemtype == 'http://schema.org/Book') {
  //              $itemtype = 'itemtype';
  //            }
  //            $itemprop = $element->getAttribute('itemprop');
  //            if ($type == '' && $itemtype == '' && $itemprop == '') {
  //              $nodeName = $attr->nodeName;
  //            }
  //            $custom_idx = $parent_node
  //                    . ($type == '' ? '' : '_' . $type)
  //                    . ($itemtype == '' ? '' : '_' . $itemtype)
  //                    . ($itemprop == '' ? '' : '_' . $itemprop)
  //                    . ($nodeName == '' ? '' : '_' . $nodeName)
  //            ;
  //            $this->meta[$this->current_folder][$this->current_file_id]['meta'][$custom_idx] = $value;
  //          }
  //        } else {
  //          $this->meta[$this->current_folder][$this->current_file_id]['meta']['@' . $parent_node] = $value;
  //        }
  //      }
  //    }
  //  }

  private function debugMeta() {
    echo '<pre>';
    var_export($this->meta['bofm']);
    // var_export($this->meta);
    echo '</pre>';
  }

  /**
   * Indents a flat JSON string to make it more human-readable.
   *
   * @param string $json The original JSON string to process.
   *
   * @return string Indented version of the original JSON string.
   */
  private function indentJson($json) {
    $result = '';
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = '&nbsp'; // '';
    $newLine = '<br/>'; //$this->new_line;
    $prevChar = '';
    $outOfQuotes = true;
    for ($i = 0; $i <= $strLen; $i++) {
      // Grab the next character in the string.
      $char = substr($json, $i, 1);
      // Are we inside a quoted string?
      if ($char == '"' && $prevChar != '\\') {
        $outOfQuotes = !$outOfQuotes;
        // If this character is the end of an element,
        // output a new line and indent the next line.
      } else if (($char == '}' || $char == ']') && $outOfQuotes) {
        $result .= $newLine;
        $pos --;
        for ($j = 0; $j < $pos; $j++) {
          $result .= $indentStr;
        }
      }
      // Add the character to the result string.
      $result .= $char;
      // If the last character was the beginning of an element,
      // output a new line and indent the next line.
      if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
        $result .= $newLine;
        if ($char == '{' || $char == '[') {
          $pos ++;
        }
        for ($j = 0; $j < $pos; $j++) {
          $result .= $indentStr;
        }
      }
      $prevChar = $char;
    }
    return $result;
  }

  /*
    Files Parsing
   */

  private function getNodeAttrs($element) {
    if ($element->hasAttributes()) {
      $attr_as_array = array();
      $attrs = $element->localName;
      foreach ($element->attributes as $attr) {
        if ($attr->localName == 'time-stamp' || $element->localName == 'pdf' || $element->localName == 'audio' || $element->localName == 'source') {
          $attr_as_array[$attr->localName] = $attr->nodeValue;
        } else if ($attr->nodeValue == 'http://schema.org/Book') {
          $attrs .= '_book';
        } else {
          $attrs .= '_' . $attr->nodeValue;
        }
      }
      if ($element->hasChildNodes()) {
        if ($element->childNodes->length == 1) {
          // No real children. Only closing Nodes
          $value = $element->nodeValue;
        } else {
          // Get Children Nodes
          $value = array(); // 'children-nodes';
          foreach ($element->childNodes as $childNode) {
            $attrsx = $this->getArrayFromNode($childNode);
            $new_idx = $childNode->localName;
            if ($attrsx !== false) {
              foreach ($attrsx as $idx => $val) {
                if ($idx <> '#text') {
                  $new_idx .= '_' . $val;
                }
              }
              $value[$new_idx] = isset($attrsx['#text']) ? $attrsx['#text'] : '';
            } else { // No Details?
//              $value[$new_idx]='--nodetails?';
            }
          }
        }
      } else {
        // No Children Nodes at all
        $value = $element->nodeValue;
      }
      if (count($attr_as_array) == 0) {
        $this->meta[$this->current_folder][$this->current_file_id]['meta'][$attrs] = $value;
      } else {
//        $attrs=($this->previous_node_parent==''?$this->previous_node_parent:'').$attrs;
        if ($value <> '') {
          $attr_as_array['value'] = $value;
        }
        $this->meta[$this->current_folder][$this->current_file_id]['meta'][$attrs][] = $attr_as_array;
      }
    }
  }

  /*
    THIS IS A RECURSIVE FUNCTION
   */

  private function scanDirectories($mainDir, $allData = array(), $level = 0, $previous_content = '') {
    $invisibleFileNames = array(".", "..", ".htaccess", ".htpasswd");
    // run through content of root directory
    $dirContent = scandir($mainDir);
    foreach ($dirContent as $key => $content) {
      // filter all files not accessible
      $path = $mainDir . '/' . $content;
      if (!in_array($content, $invisibleFileNames)) {
        // if content is file & readable, add to array
        if (is_file($path) && is_readable($path)) {
          // save file name with path
          $allData[] = $path;
          // if content is a directory and readable, add path and name
        } elseif (is_dir($path) && is_readable($path)) {
          // recursive callback to open new directory
          $level++;
          // echo "path $level $path<br/>";
          $allData = $this->scanDirectories($path, $allData, $level, $content); // recursivity goes here
          $book = $path;
          if ($level == 0) {
            $this->books_sub[$mainDir] = array();
          } else if ($level > 0) {
            $this->books_sub[$mainDir][] = $content; //$book;
            // echo $level . ' --- ' . $mainDir.'<br/>';
          }
          $level--;
        }
      }
    }

    return $allData;
  }

  /*
    Generic Classes, simple classes.
   */

  private function printTitle($title) {
    if ($this->printedTitle === FALSE) {
      $this->printedTitle = TRUE;
      echo $title;
    }
  }

  private function htmlHeader() {
    header("Content-Type: text/html; charset=utf-8");
    ?><!DOCTYPE html>
    <html>
      <title>
        Bible HTML Parsing
      </title>
      <body>
        <?php $this->setForm() ?>
        <div style="background-color:Tomato;color:white;padding: 0.01em 10px;padding-top: 10px; padding-bottom: 10px"'>
          cmd=<?php echo $this->cmd ?>&lang=<?php echo $this->language ?>
          <br />Source Path <?php echo $this->path_source ?>
          <br />Target Path <?php echo $this->path_target ?></div>
        <?php
        $link = str_replace('/Applications/MAMP/htdocs', 'localhost:8888', $this->path_target);
        ?>
        <br /><a href="<?php echo 'http://' . $link ?>" target="_blank"><?php echo $link ?></a>
        <?php
        $link = str_replace('/Applications/MAMP/htdocs/bomc/original', 'scriptures.bomc.dev2.nuvek.com/2013', $this->path_source);
        ?>
        <br /><a href="<?php echo 'http://' . $link ?>" target="_blank">http://<?php echo $link ?></a>
      </div>
      <h1 style="background-color:DodgerBlue;color:white;">BOMC (<?php echo $this->language ?>)</h1>
      <?php
      flush();
    }

    private function htmlFooter() {
      ?><span style="color:blue;border-color:orange;border-style: double;">completed!</span>
    </body>
    </html><?php
  }

  private function setForm() {
    ?>
    <div style="background-color:#9CBC2C;max-width: 320px;margin: auto;border: 3px solid #73AD21; border-radius: 15px 50px; ">
      <form action="parseBible.php" method="get">
        <span style="margin: 50px;background-color:#B9CF6A; auto;border: 1px solid #E3EBC3">
          <select name="cmd">
            <option value="media" <?php echo $this->cmd == 'media' ? 'selected' : '' ?>>Show Media embedded</option>
            <option value="content" <?php echo $this->cmd == 'content' ? 'selected' : '' ?>>Show Content</option>
            <option value="html" <?php echo $this->cmd == 'html' ? 'selected' : '' ?>>Create HTML files and Navigation</option>
            <option value="summary" <?php echo $this->cmd == 'summary' ? 'selected' : '' ?>>summary</option>
          </select>
        </span><br/>
        <span style="margin: 50px;background-color:#B9CF6A; auto;border: 1px solid #E3EBC3">
          <input type="radio" name ="lang" value="spanish" <?php echo $this->language == 'spanish' ? 'checked' : '' ?>><Label for="spanish">Spanish</label>
          <input type="radio" name ="lang" value="english" <?php echo $this->language == 'english' ? 'checked' : '' ?>><Label for="spanish">English</label>
          <input type="radio" name ="lang" value="portuguese" <?php echo $this->language == 'portuguese' ? 'checked' : '' ?>><Label for="portuguese">Portuguese</label><br/>
        </span>
        <span style="margin: 50px;background-color:#B9CF6A; auto;border: 1px solid #E3EBC3">
          <input type="submit" value="Execute">
        </span>
      </form>
    </div>
    <?php
  }

}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Script Execution
$obj = new parseBible();
$obj->execute();
