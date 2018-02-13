<?php

require_once('folderFilesReader.php');

class createIndexFiles extends folderFilesReader {

  private $scripturesIndex;
  public $books_sub = array();
  private $links_book = '';
  private $links = array();
  private $links_map = '';
  private $link_last = '';
  private $temp_previous_dom_href_dom_level = 0;
  private $temp_previous_dom_href_map = 0;
  private $no_map_seq = 0;
  private $full_book = array();

  function __construct() {
    parent::__construct();
  }

  public function execute() {
    $this->constructCSSFiles();
    // echo 'path_source:'.$this->path_source.'<br/>';
    // echo 'path_target:'.$this->path_target.'<br/>';
    // $this->array2ul($this->books_sub);
    $this->createScripturesIndexFile();
  }

  private function createScripturesIndexFile() {
    $this->msgProgress('Creating Index Files into <b>' . $this->path_target . '</b>');
    $this->scripturesIndex = new createHTML();
    $this->scripturesIndex->setFileName($this->path_target . '/index.html');
    $this->scripturesIndex->setHead();
    $this->scripturesIndex->setContent($this->indexHtmlTopNav());
    $this->scripturesIndex->setContent($this->indexHtmlNavStart());
    $this->createScriptureIndex($this->books_sub);
    $this->scripturesIndex->setContent($this->indexHtmlNavEnd());
    $this->scripturesIndex->setFooter();
    $this->scripturesIndex->createFile();
  }

  /*
    Also, make sure these are included in <link rel="stylesheet"
    parseBible.php->newCSS()
   */

  private function constructCSSFiles() {
    $new_styles = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder
            , $this->main_folder . '/fonts');
    $this->msgProgress('Copying Font files into <b>' . $new_styles . '</b>');
    $this->createDir($new_styles);
    $files = array();
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/fonts/pala.ttf', '/pala.ttf');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/fonts/palab.ttf', '/palab.ttf');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/fonts/palabi.ttf', '/palabi.ttf');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/fonts/palai.ttf', '/palai.ttf');
    foreach ($files as $key => $file) {
      copy($file[0], $new_styles . $file[1]);
    }

    $new_styles = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder
            , $this->main_folder . '/styles');
    $this->msgProgress('Downloading/Copying CSS files into <b>' . $new_styles . '</b>');
    $this->createDir($new_styles);
    $files = array();
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/styles/animate.css', '/animate.css');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/styles/navigation.css', '/navigation.css');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/styles/scriptures.css', '/scriptures.css');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/styles/responsive.css', '/responsive.css');
    foreach ($files as $key => $file) {
      copy($file[0], $new_styles . $file[1]);
    }

    // temp clear main.css
    // $tmp_text = '/* Temporary cleared by script ' . __FILE__ . ' line ' . __LINE__ . ' */';
    // file_put_contents($new_styles . '/main.css', $tmp_text);
    $files = array();
    // $files[] = array('https://edge.ldscdn.org/cdn2/csp/ldsorg/css/pages/scriptures.css', 'scriptures.css');
    // $files[] = array('https://edge.ldscdn.org/cdn2/csp/ldsorg/css/common/content.css', 'content.css');
    // $files[]=array('https://edge.ldscdn.org/cdn2/csp/ldsorg/css/common/lds-old.css','lds-old.css');
    $files[] = array('https://fonts.googleapis.com/css?family=Open+Sans', 'googleOpenSans.css');
    foreach ($files as $key => $file) {
      $this->downloadAndSaveFile($file[0], $file[1], $new_styles);
    }

    $new_styles = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder
            , $this->main_folder . '/js');
    $this->msgProgress('Downloading/Copying JS files into <b>' . $new_styles . '</b>');
    $this->createDir($new_styles);
    $files = array();
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/js/hammer.min.js', '/hammer.min.js');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/js/functions.js', '/functions.js');
    $files[] = array('/Applications/MAMP/htdocs/bomc/dev/js/functions_index.js', '/functions_index.js');
    foreach ($files as $key => $file) {
      copy($file[0], $new_styles . $file[1]);
    }
  }

  /*
    Create the Main Index, for all the library, only 5 books, btw
   */

  private function createScriptureIndex($books, $main_book = '', $level = 0) {
    foreach ($books as $book => $subook) { // Read ALL books.
      $level++;
      if (is_array($subook)) {
        // Read Book Book Collection to get into individual books
        $split = explode('/', $book);
        // echo $this->identation($level).'<img src="https://png.icons8.com/books/color/20/000000">' . $level . ' -- ' . $main_book . ' :: ' . $book . '<sup>' .count($split).'</sup><br/>';
        if (count($split) < 9 || in_array(end($split), $this->only_books)) {
          if ($main_book == '' && $book <> $this->path_source) {
            // echo $this->identation($level+3).'<sup style="color:blue;">Create book index from the <em>_manifest.html</em> file</sup><br/>';
            $this->clearLinks();
            $this->createBookIndex($book, $level);
          } else {
            // echo $this->identation($level+3).'<sup style="color:DarkGreen;">stay in this level to create index links</sup><br/>';
          }
          $this->createScriptureIndex($subook, $book, $level);
        } else {
          // Book not included on the list to create index file.
          // echo $this->identation($level+3).'<sup style="color:red;">books not included on approved list to create index link files</sup><br/>';
        }
      } else {
        if ($main_book == $this->path_source) {
          if (in_array($subook, $this->only_books)) {
            // Include this Book
            // echo $this->identation($level).'<img src="https://png.icons8.com/checked/color/12/000000">' . $level . ' -- ' . $main_book . ' <em>/' . $subook.'<sup>' .$book. '</sup></em><br/>';
            $this->createScriptureIndexMainFile($main_book, $subook);
          } else {
            // Ignore this book
            // echo $this->identation($level).'<img src="https://png.icons8.com/unavailable/color/12/000000">' . $level . ' -- ' . $main_book . ' <em>/' . $subook.'<sup>' .$book. '</sup></em><br/>';
          }
        } else {
          // Book should be ignored
          // echo $this->identation($level).'<img src="https://png.icons8.com/read/color/16/000000">' . $level . ' -- ' . $main_book . ' <em>/'  . $subook.'</em><sup>'.$book .'</sup>'
          // . $this->identation($level+3).'<sup style="color:blue;">Nothing to do here.</sup>'
          // . '<br />';
        }
      }
      $level--;
    }
    flush();
  }

  private function createScriptureIndexMainFile($main_book, $book) {
    $href = $book . '/index.html';
    $file = $main_book . '/' . $book . '/_manifest.html';
    $name = $this->getManifestTitle($file);
    // echo ' ~~~~~~ Create Main Index Book: '.$file.'<br />';
    // echo '$href '.$href.' --- '.$this->scripturesIndex->getFileName().'<br/>';
    $this->scripturesIndex->setHref($href, $name, 0);
  }

  private function clearLinks() {
    $this->links_book = '';
    $this->links = array();
    $this->links_map = '';
    $this->link_last = '';
    $this->temp_previous_dom_href_map = '';
    $this->temp_previous_dom_href_dom_level = '';
  }

  private function createBookIndex($book, $level) {
    $this->clearLinks();
    $file = $book . '/_manifest.html';
    $this->readManifestFileToGetLinks($file, $level);
    $this->createIndexChaptersFromArrayLink($file, $level);
  }

  private function readManifestFileToGetLinks($file, $level) {
    $dom = new DOMDocument();
    $dom->load($file);
    $tag = 'header';
    $array = array();
    foreach ($dom->getElementsByTagName($tag) as $node) {
      $array[$node->nodeName][] = $this->getArrayFromNode($node);
    }
    // echo $array['header'][0]['h1'][0]['#text'].'<br/>';
    $this->links_book = $array['header'][0]['h1'][0]['#text'];
    $tag = 'nav';
    $array = array();
    foreach ($dom->getElementsByTagName($tag) as $node) {
      $array[$node->nodeName][] = $this->getArrayFromNode($node);
    }
    if (strpos($file, '/pgp/') !== false) {
      // these links do not follow the structure of other files,
      // so I add them manually here to do not add more logic
      $this->links['first_links'] = array('title-page.html' => 'Title Page'
          , 'introduction.html' => 'Introduction');
    }
    $this->readIndexMapToCreateLinks($file, $array);
  }

  private function createIndexChaptersFromArrayLink($file, $level) {
    $this->full_book = array();
    // echo '<fieldset style="border: 1px double SlateBlue;">';
    $new_file_book_index = str_replace($this->scriptures_folder
            , $this->scriptures_modified_folder, $file);
    // echo '$scriptures_folder <span style="color:red;">'.htmlentities($this->scriptures_folder).'</span><br/>';
    // echo '$scriptures_modified_folder <span style="color:red;">'.htmlentities($this->scriptures_modified_folder).'</span><br/>';
    // echo '$new_file_book_index <span style="color:black;">'.htmlentities($new_file_book_index).'</span><br/>';
    $new_file_book_index = str_replace('_manifest.html', 'index.html', $new_file_book_index);
    // echo 'extracting hrefs from $file <span style="color:fuchsia;">'.htmlentities($file).'</span><br/>';
    // echo '$this->scriptures_modified_folder <span style="color:fuchsia;">'.htmlentities($this->scriptures_modified_folder).'</span><br/>';
    $subBook = new createHTML();
    $subBook->setFileName($new_file_book_index);
    $subBook->setHead($this->links_book, '../../../');
    ///
    $subBook->setContent($this->indexHtmlNavStart($this->links_book));
    $subBook->setContent('<div class="wrapper">' . $this->new_line); // responsive
    $subBook->setContent('<span class="rcorners2">General Notes</span>' . $this->new_line); // Index Books
    $clearfix = false;
    ///
    if (strpos($file, '/dc-testament/') !== false) {
      $subBook->setContent('<div class="row clearfix">' . $this->new_line); // close this later
      $clearfix = true;
      foreach ($this->links as $key_main => $value_main) {
        // echo ' | value_main <span style="color:blue;">'.$value_main.'</span><br/>';
        $this->createIndexChaptersHrefsSimple($value_main, $subBook, 'col-1-6', $new_file_book_index);
      }
    } else {
      if (strpos($file, '/pgp/') !== false) {
        $subBook->setContent('<div class="row clearfix">' . $this->new_line); // close this later
        $clearfix = true;
        // when is not Pearl of Grace, no subooks, only direct links.
        $this->createIndexChaptersHrefsSimple($this->links['first_links'], $subBook, 'col-1-2', $new_file_book_index);
        $class_for_this_subbook = 'col-1-1';
      } else {
        $subBook->setContent('<div class="clearfix">' . $this->new_line); // close this later
        $clearfix = true;
        $class_for_this_subbook = 'left-right_columns';
      }
      $this->createIndexChaptersHrefs($subBook, $new_file_book_index, $level, $class_for_this_subbook);
    }
    if ($clearfix) {
      $subBook->setContent('</div>'); // clearfix
    }
    $subBook->setContent('</div>'); // wrapper
    $subBook->setContent($this->indexHtmlNavEnd());
    $subBook->setFooter('../../../');
    $subBook->createFile();
    // echo '<b>Index File was created $new_file_book_index <span style="color:fuchsia;">'.htmlentities($subBook->getFileName()).'</span></b>';
    // echo '</fieldset>';
    $this->addMetaChapters();
  }

  private function getBookChapterName($name) {
    // echo $this->language.'<br/>';
    if ($name == 'Psalms') {
      $name_temp = 'Psalm';
    } else if ($name == 'Salmos') {
      $name_temp = 'Salmo';
    } else {
      $name_temp = $name;
    }
    return $name_temp;
  }

  private function createIndexChaptersHrefsSimple($hrefs, &$subBook, $class_columns, $new_file_book_index) {
    $chapters=array();
    $chapter_count=0;
    $hasSlash = false;
    foreach ($hrefs as $key => $value) {
      if (strpos($key, '.html') !== false && (in_array($key, $this->files_to_ignore) === false)) {
        $href = $key;
        $name = $value;
        $name_temp = $this->getBookChapterName($name);
        if (strpos($name, $this->links_book . ' ') !== false) {
          // remove the part of the chapter name that matches the title of the page.
          // echo 'here: '.__FUNCTION__.'--'.__LINE__.' name:'.$name_temp.'<br/>';
          $name = str_replace($this->links_book, '', $name_temp);
        }
        // echo ' | $href [<span style="color:blue;">'.htmlentities($href).']</span>';
        // echo ' | $name [<span style="color:blue;">'.htmlentities($name).']</span>';
        // echo '<br/>';
        if ($href == 'title-page.html' || $href == 'introduction.html' || $href == 'od/1.html' || $href == 'od/2.html') {
          $this_simple_book_column = 'col-1-1';
        } else {
          $this_simple_book_column = $class_columns;
        }
        // echo ' | $book_column_class [<span style="color:green;">'.htmlentities($this_simple_book_column).'</span>]';
        // echo '<br/>';
        $subBook->setContent('<div class="' . $this_simple_book_column . '">' . $this->new_line); // Index HTML chapters
        $subBook->setHref($href, $name, 1);
        $subBook->setContent('</div>' . $this->new_line);

        //echo 'path '.$href.'<br>';
        if (in_array($href, $this->files_to_ignore) === false) {

          $hrl = $href;

          if (strrpos($href, "/") === false) {
            if ($hasSlash === true) {
              $this->readChaptersArray($chapters);
              $chapters = array();
              $chapter_count=0;
            }
            
            $hasSlash = false;
          } else {
            if ($hasSlash === false) {
              $this->readChaptersArray($chapters);
              $chapters = array();
              $chapter_count=0;
            }

            $hasSlash = true;
          }
          $hrl = explode('/', $href);
          $hrl = isset($hrl[1]) ? $hrl[1] : $hrl[0];
          $file_to_include_navigations=str_replace('index.html', '', $new_file_book_index).htmlentities($href);
          //echo 'path '.$file_to_include_navigations.'<br>';
          //echo 'hrl '.$hrl.'<br>';
          $chapter_count++;
          $chapters[$chapter_count]=array('file'=>$file_to_include_navigations
                                          ,'hrl'=>$hrl
          );
        }

      }
    }

    $this->readChaptersArray($chapters);
  }

  /*
    Here the File Index for Book Chapters is created.
    -Chapters by reading all the book/chapters.html
    -Or created a new index_*.html file
   */

  private function createIndexChaptersHrefs(&$subBook, $new_file_book_index, $level, $class_columns) {
    // echo 'empieza createIndexChaptersHrefs<br/>';
    // echo ' new_file_book_index <span style="color:blue;">'.$new_file_book_index.'</span>';
    // echo '<br/>';
    // $this->array2ul($this->links);
    if (strpos($new_file_book_index, '/bofm/index.html') !== false) {
      $value_ordered = $this->orderBooksChapters($this->links);
    } else {
      $value_ordered = $this->links;
    }
    $rows_class = "";
    $first_row_offset = 0; //if 1 first row only have 1 item
    if (strpos($new_file_book_index, '/bofm/index.html') > 0) {
      $rows_class = "bofm";
      $first_row_offset = 1;
      if (count($value_ordered) > 15) {
        $rows_class = "bofm_en";
      }
    } else if (strpos($new_file_book_index, '/ot/index.html') > 0) {
      $rows_class = "ot";
      $first_row_offset = 0;
    } else if (strpos($new_file_book_index, '/nt/index.html') > 0) {
      $rows_class = "nt";
      $first_row_offset = 0;
    }
    if ($class_columns == 'left-right_columns') {
      //$subBook->setContent('<div id="left">' . $this->new_line); // close this later
      // echo '$value_ordered['.count($value_ordered).']<br/>';
      
      $subBook->setContent('<div class="multirow '.$rows_class.'">' . $this->new_line); // close this later
    }

    $i = 1;
    if (fmod((count($value_ordered) + $first_row_offset), 2) > 0) {
      $i = 0;
    }

    $hrefs_count = 0;
    $printed_half_div = false;
    foreach ($value_ordered as $key => $value) { // Read Links recovered from _manifest.html file, and create the Index.html of those links.
      if ($key == 'first_links') {
        continue;
      }
      $hrefs_count++;
      if ($class_columns == 'left-right_columns' && $printed_half_div === false) {
        if ($hrefs_count >= (ceil((count($value_ordered) + $first_row_offset) / 2) + $i)) {
          // echo 'value_ordered['.count($value_ordered).'] hrefs_count['.$hrefs_count.'] round(count($value_ordered)/2) ['.round(count($value_ordered)/2).']<br/>';
          // Print Right Column Div to print the second half of the books
          //$subBook->setContent('</div>' . $this->new_line . '<div id="right">' . $this->new_line); // close this later
          $printed_half_div = true;
          if ($first_row_offset > 0) {
            $subBook->setContent('<div></div>' . $this->new_line); // empty cell in first row
          }
          if (fmod((count($value_ordered) + $first_row_offset), 2) > 0) {
            $subBook->setContent('<div></div>' . $this->new_line); // empty cell in first row
          }
        }
      }
      // echo '<fieldset style="border: 1px double SlateBlue;">';
      // echo ' | key <span style="color:blue;">'.$key.'</span>';
      // $this->array2ul($value, '$value for books');
      if (strpos($value['index'], '.html') === false) {
        $href = $value['index'] . '/index.html';
        $parent_folder = '../../../../';
        // sub-books tend to have numeric chapters
        $book_column_class = 'col-1-6'; // 6 columns
      } else {
        $href = 'index_' . $value['index'];
        $parent_folder = '../../../';
        // sub-books tend to have texty chapters
        $book_column_class = 'col-1-1'; // 1 Column
      }
      if (count($value) <= 3) {
        // for subooks with less than 3 books
        $book_column_class = 'col-1-1'; // 1 Column 
      }
      if ($href == 'index_title-page.html' || $href == 'index_pronunciation.html') {
        // then there is only one book, or specific books, show only one column per row
        // echo 'set columns<br/>';
        $class_columns_for_this_book = 'col-1-1';
      } else {
        $class_columns_for_this_book = $class_columns;
      }
      // $href = strpos($value['index'], '.html') === false ? $value['index'] . '/index.html' : 'index_' . $value['index'];
      $name = $value['title'];
      // echo ' | value[\'index\'] <span style="color:blue;">'.$value['index'].'</span>';
      // echo ' | $href [<span style="color:blue;">'.htmlentities($href).'</span>]';
      // echo ' | $book_column_class [<span style="color:green;">'.htmlentities($book_column_class).'</span>]';
      // echo ' | count(sub-books) ['.(count($value)<=3?'<b>':'').'<span style="color:tomato;">'.count($value).'</span>]'.(count($value)<=3?'</b>':'').'';
      // echo ' | $name [<span style="color:tomato;">'.htmlentities($name).']</span>';
      // Create the Lin for the Subbook
      if ($class_columns == 'left-right_columns') {
        $subBook->setContent('<div>' . $this->new_line); // Index Books
      } else {
        $subBook->setContent('<div class="' . $class_columns_for_this_book . '">' . $this->new_line); // Index Books
      }
      $subBook->setHref($href, $name, 0);
      $subBook->setContent('</div>' . $this->new_line);
      // Now Create Index.html of the subboks.
      $this->readManifestToCreateBookIndex($value, $new_file_book_index, $name, $href, $value['index'], $level, $parent_folder, $book_column_class);
      // echo '</fieldset>';
      // echo '<br/>';
    }
    if ($class_columns == 'left-right_columns') {
      $subBook->setContent('</div>' . $this->new_line); // close this later
    }
  }

  private function orderBooksChapters($value) {
    return $value;
    $value_ordered = array();
    $temp = 0;
    // first, go first!
    // $value_ordered['title']=$value['title'];
    // $value_ordered['index']=$value['index'];
    $count = 0;
    echo '<br/>';
    //even
    foreach ($value as $k => $v) {
      if (!($k == 'title' || $k == 'index')) {
        if ($count++ % 2 == 0) {
          // echo '$k ['.$k.'] --  $v ['.$v.']<br/>';
          $value_ordered[$k] = $v;
          if ($k == 'index_title-page.html') {
            $value_ordered['hello' . $temp++] = $v;
          }
        }
      }
    }
    //odd
    $count = 0;
    foreach ($value as $k => $v) {
      if (!($k == 'title' || $k == 'index')) {
        if ($count++ % 2 != 0) {
          // echo '$k ['.$k.'] --  $v ['.$v.']<br/>';
          $value_ordered[$k] = $v;
          if ($k == 'index_title-page.html') {
            $value_ordered['hello' . $temp++] = $v;
          }
        }
      }
    }
    echo '<pre>';
    var_export($value_ordered);
    echo '<br />****</pre>';
    return $value_ordered;
  }

  /*

   */

  private function readManifestToCreateBookIndex($value, $file, $name, $href, $value_index, $level, $parent_folder, $book_column_class) {
    // echo '<fieldset style="border: 1px double SlateBlue;">';
    // if(strpos($value_index,'.html')===false) {
    // echo '<span style="background-color:yellow">'.'adding '.$value_index.' into '. $file.'</span><br/>';
    // }
    $new_chapter_file = str_replace('index.html', $href, $file);
    // echo __FUNCTION__.'()<br/>';
    // // echo ' <b>$value</b> <span style="color:red;">'.htmlentities($value).'</span><br/>';
    // echo ' <b>$file</b> <span style="color:red;">'.htmlentities($file).'</span><br/>';
    // echo ' <b>$name</b> <span style="color:red;">'.htmlentities($name).'</span><br/>';
    // echo ' <b>$href</b> <span style="color:red;">'.htmlentities($href).'</span><br/>';
    // echo ' <b>$value_index</b> <span style="color:red;">'.htmlentities($value_index).'</span><br/>';
    // echo ' <b>$level</b> <span style="color:red;">'.htmlentities($level).'</span><br/>';
    // echo ' <b>$parent_folder</b> <span style="color:red;">'.htmlentities($parent_folder).'</span><br/>';
    // echo ' <b>$book_column_class</b> <span style="color:red;">'.htmlentities($book_column_class).'</span><br/>';
    // echo ' <b>$new_chapter_file</b> <span style="color:red;">'.htmlentities($new_chapter_file).'</span><br/>';
    $bookChapters = new createHTML();
    $bookChapters->setFileName($new_chapter_file);
    $bookChapters->setHead($name, $parent_folder);
    $bookChapters->setContent($this->indexHtmlNavStart($name));
    //debug
    // echo 'array count: '.count($value).'<br/>';
    // $this->array2ul($value, '$value for books');
    // echo '<br/>Chapters:<br/>';
    $bookChapters->setContent('<div class="wrapper">' . $this->new_line); // responsive
    $bookChapters->setContent('<span class="rcorners2">General Notes</span>' . $this->new_line); // Index Books
    $bookChapters->setContent('<div class="row clearfix">' . $this->new_line); // responsive
    $this->setBookIndexChaptersHrefs($bookChapters,$value,$file,$name,$book_column_class);
    $bookChapters->setContent('</div>'); // responsive
    $bookChapters->setContent('</div>'); // responsive
    $bookChapters->setContent($this->indexHtmlNavEnd());
    $bookChapters->setFooter($parent_folder);
    // echo 'Chapters file to be created <span style="color:Magenta;">'.$bookChapters->getFileName().'</span><br/>';
    $bookChapters->createFile();
    // echo '<b>Chapter Index File was created <span style="color:fuchsia;">'.$bookChapters->getFileName().'</span></b>';
    // echo '</fieldset>';
    flush();
  }

  private function setBookIndexChaptersHrefs(&$bookChapters,$value,$file,$name,$book_column_class) {
    $chapters = $this->createBookIndexChaptersHrefs($bookChapters,$value,$file,$name,$book_column_class);
    $this->readChaptersArray($chapters);
  }

  private function createBookIndexChaptersHrefs(&$bookChapters,$value,$file,$name,$book_column_class) {
    // echo '<b>$value:</b><br />';
    $chapters=array();
    $chapter_count=0;
    foreach ($value as $k => $v) {
      if ($k != 'title' && $k != 'index') {
        $hrl = explode('/', $k);
        $hrl = isset($hrl[1]) ? $hrl[1] : $hrl[0];
        $hrd = $v;
        $name_temp = $this->getBookChapterName($name);
        if (strpos($v, $name_temp . ' ') !== false) {
          // remove the part of the chapter name that matches the title of the page.
          // echo 'here: '.__FUNCTION__.'--'.__LINE__.' name:'.$name_temp.'<br/>';
          $hrd = str_replace($name_temp, '', $v);
        }
        if (in_array($k, $this->files_to_ignore) === false) {

          $chapter_count++;
          $file_to_include_navigations=str_replace('index.html', '', $file).$k;
          $chapters[$chapter_count]=array('file'=>$file_to_include_navigations
                                        ,'hrl'=>$hrl
        );
        if ($hrl == 'fac-1.html' || $hrl == 'fac-2.html' || $hrl == 'fac-3.html') {
          $book_column_class = 'col-1-1'; 
        }
          
          // echo 'Index Chapter '
          // . '$k[<span style="color:blue;">'.$k.'</span>]'
          // . ' | $v<span style="color:blue;">['.$v.'</span>]'
          //       .' | $hrd[<span style="color:blue;">'.$hrd.'</span>]'
          //       .' | $hrl[<span style="color:blue;">'.htmlentities($hrl).'</span>]'
          //       .' | $name[<span style="color:blue;">'.$name.'</span>]'
          //       .' | $file_to_include_navigations[<span style="color:blue;">'.$file_to_include_navigations.'</span>]'
          // .'<br/>';
          // echo $v.'<br/>';
          $bookChapters->setContent('<div class="' . $book_column_class . '">' . $this->new_line); // chapter
          $bookChapters->setHref($hrl, $hrd, 1);
          $bookChapters->setContent('</div>' . $this->new_line);
        } else {
          // echo 'Ignore this file '
          // . '$k[<span style="color:blue;">'.$k.'</span>]'
          // . ' | $v<span style="color:blue;">['.$v.'</span>]'
          //       .' | $hrd[<span style="color:blue;">'.$hrd.'</span>]'
          //       .' | $hrl[<span style="color:blue;">'.htmlentities($hrl).'</span>]'
          //       .' | $name[<span style="color:blue;">'.$name.'</span>]'
          // .'<br/>';
          // echo $v.'<br/>';
        }
      }
    }
    return $chapters;
  }

  private function readChaptersArray($chapters) {
    // $this->array2ul($chapters);
    foreach($chapters as $k=>$v) {
      // echo $k.' => '.$v['file'].'<br/>';
      $split = explode('/', $v['file']);

      $book = '';
      $folder = '';
      $fileName = '';
      $i = 0;
      foreach ($split as $value) {
        //echo 'value: '.$value.'<br>';
        if ($value === 'scriptures') {
          $book = $split[$i + 1];
          if (count($split) == ($i + 3)) {
            $fileName = $split[$i + 2];
          } else {
            $folder = $split[$i + 2];
            $fileName = $split[$i + 3];
          }
          break;
        }
        $i++;
      }
      /*echo 'book: '.$book.'<br>';
      echo 'folder: '.$folder.'<br>';
      echo 'file: '.$file.'<br>';*/
      $chapter=array('file'=>$v['file'],'hrl'=>$v['hrl'],'book'=>$book,'folder'=>$folder,'fileName'=>$fileName);

      array_push($this->full_book, $chapter);
    }
  }

  private function addMetaChapters() {
    //$this->array2ul($this->full_book);
    
    $count=0;
    foreach($this->full_book as $k=>$v) {
      
      //echo $k.' => '.$v['file'].'<br/>';
      $hrl_prev = '';
      $hrl_next = '';
      $prefix = '';
      $folder = $v['folder'];
      if(isset($this->full_book[$count-1])) {
        if ($this->full_book[$count-1]['folder'] !== '') {
          if ($folder === '') {
            $prefix = $this->full_book[$count-1]['folder'].'/';
          } else {
            if ($this->full_book[$count-1]['folder'] !== $folder) {
              $prefix = '../'.$this->full_book[$count-1]['folder'].'/';
            }
          }
        } else {
          if ($this->full_book[$count-1]['folder'] !== $folder) {
            $prefix = '../';
          }
        }
        $hrl_prev=$prefix.$this->full_book[$count-1]['hrl'];
        // echo 'prev: '.$hrl_prev.'<br/>';
      }
      $prefix = '';
      if(isset($this->full_book[$count+1])) {
        if ($this->full_book[$count+1]['folder'] !== '') {
          if ($folder === '') {
            $prefix = $this->full_book[$count+1]['folder'].'/';
          } else {
            if ($this->full_book[$count+1]['folder'] !== $folder) {
              $prefix = '../'.$this->full_book[$count+1]['folder'].'/';
            }
          }
        } else {
          if ($this->full_book[$count+1]['folder'] !== $folder) {
            $prefix = '../';
          }
        }
        $hrl_next=$prefix.$this->full_book[$count+1]['hrl'];
        // echo 'next: '.$hrl_next.'<br/>';
      }
      // Modify the File.
      $downloaded_stream = file_get_contents($v['file']);
      $downloaded_stream = $this->insertString('<meta charset="UTF-8"/>', $downloaded_stream
                              ,"\n".'<meta chapter_prev="'.$hrl_prev.'">'
                                .'<meta chapter_next="'.$hrl_next.'">'."\n"
                            );
      // $link = '<a href="'.$hrl_prev.'">&lt;&lt;</a>'
      //                       .'&nbsp;&nbsp;&nbsp;&nbsp;'
      //                       .'<a href="'.$hrl_next.'">&gt;&gt;</a>';
      // $downloaded_stream = $this->insertString('<body>', $downloaded_stream
      //                         ,"\n".$link."\n"
      //                       );
              
      file_put_contents($v['file'], $downloaded_stream);

      $count++;
    }
  }

  private function getManifestTitle($file) {
    $title = '';
    $dom = new DOMDocument();
    $dom->load($file);
    foreach ($dom->getElementsByTagNameNS('http://www.lds.org/schema/lds-meta/v1', '*') as $element) {
      if ($element->localName == 'title') {
        $type = $element->getAttribute('type');
        if ($type == 'navigation') {
          // echo $file.'<br />';
          // echo $element->nodeValue . '<br />';
          $title = $element->nodeValue;
        }
      }
    }
    return $title;
  }

  private function readIndexMapToCreateLinks($file, $array
  , $level_dom = 0
  , &$found = array(1 => false, 2 => false, 'xtract' => false, 'a' => false, 'b' => false, 'c' => false, 'xtractL' => false)
  , $node_name = null, $node_value = null
  ) {
    if (strpos($file, '/dc-testament/') !== false) {
      //echo 'problem with '.$file.'<br/>';
      $found[1] = true;
      $found[2] = true;
      $found['xtract'] = true;
    }
    $find = array(
        1 => array('node' => 'href', 'value' => '#map')
        , 2 => array('node' => 'class', 'value' => 'title')
        , 'xtract' => array('node' => '#text', 'value' => null)
        , 'a' => array('node' => 'class', 'value' => 'doc-map')
        , 'b' => array('node' => 'href')
        , 'c' => array('node' => 'class', 'value' => 'title')
        , 'xtractL' => array('node' => '#text', 'value' => null)
    );
    foreach ($array as $idx => $key) {
// echo '<fieldset style="border: 0.5px dotted #000;">';
      $level_dom++;
      if (is_array($key)) {
        $this->readIndexMapToCreateLinks($file, $key, $level_dom, $found);
      } else if ($idx == $find[1]['node'] && strpos($key, $find[1]['value']) !== false) {
// echo '<span style="color:blue;">Dom Level[<em>'.$level_dom.'</em>] starting to define ' . $idx . ' = ' . $key . '</span>';
        $this->links_map = $key;
        foreach ($found as $k => $v) {
          $found[$k] = false;
        }
        $found[1] = true;
        $this->temp_previous_dom_href_dom_level == 0;
      } else if ($found['xtract'] === false && $found[1] && ($idx == $find[2]['node'] && $key == $find[2]['value'])) {
// echo '<em>found 1 and 2 '.$idx.' => '.$key.'</em>';
        $found[2] = true;
      } else if ($found['xtract'] === false && $found[1] && $found[2] && $idx == $find['xtract']['node']) {
// echo '<u>extract '.$idx.' => '.$key.'</u>';
        $found['xtract'] = true;
        $found['a'] = false;
        $found['b'] = false;
        $found['c'] = false;
        $found['xtractL'] = false;
        $this->links[$this->links_map]['title'] = $key;
      } else if ($found['xtract'] && ($idx == $find['a']['node'] && $key == $find['a']['value'])) {
// echo '<u>links start '.$idx.' => '.$key.'</u>';
        $found['a'] = true;
        $found['b'] = false;
        $found['c'] = false;
        $found['xtractL'] = false;
      } else if ($found['a'] && $idx == $find['b']['node']) {
        if (
                ($level_dom <= $this->temp_previous_dom_href_dom_level &&
                $this->temp_previous_dom_href_map == $this->links_map) || strpos($this->links_map, '#no_map') !== false
        ) {
// echo '<div style="background-color:yellow;color:red;">'
//       .'Mismatch! level_dom['.$level_dom.']'
//       .' $this-temp_previous_dom_href_dom_level['.$this->temp_previous_dom_href_dom_level.']'
//       .'<br/>map[<em>'.$this->links_map.''.'</em>]'
//       .' old map[<em>'.$this->temp_previous_dom_href_map.'</em>]'
//       .'</div>'
//       .($this->temp_previous_dom_href_map!=$this->links_map?'<div style="background-color:gray;color:white">and might a new list'.'</div>':'')
//     ;
          $this->links_map = '#no_map' . $this->no_map_seq++;
        }
        $this->temp_previous_dom_href_map = $this->links_map;
        $this->temp_previous_dom_href_dom_level = $level_dom;
// echo 
// 'Dom Level[<em>'.$level_dom.'</em>]'
// . ' | previous map:'. $this->temp_previous_dom_href_map
// . ' | $temp_previous_dom_href_dom_level['.$this->temp_previous_dom_href_dom_level.']'
// . ' <br/> '
// .
// 'final link key '.$idx.' => '.$key.' added into '.$this->links_map 
// ;
        if (!isset($this->links[$this->links_map]['index'])) {
          $split = explode('/', $key);
          $this->links[$this->links_map]['index'] = $split[0];
        }
        $this->links[$this->links_map][$key] = '';
        $this->link_last = $key;
// echo $this->link_last.'';
        $found['b'] = true;
        $found['c'] = false;
        $found['xtractL'] = false;
// echo '<br/>'.htmlentities('$this->links[$this->links_map][\'index\']').' = ' .$this->links[$this->links_map]['index'].'<br/>';
      } else if ($found['xtractL'] === false && $found['a'] && $found['b'] && $idx == $find['c']['node']) {
// echo '<u>text link '.$idx.' => '.$key.'</u>';
        $found['c'] = true;
        $found['xtractL'] = false;
      } else if ($found['xtractL'] === false && $found['a'] && $found['b'] && $found['c'] && $idx == $find['xtractL']['node']) {
// echo '<u>final link name '.$idx.' => '.$key.'</u>'.' added into '.$this->links_map;
        $this->links[$this->links_map][$this->link_last] = $key;
        $found['xtractL'] = true;
        if (!isset($this->links[$this->links_map]['title'])) {
// does not exist, create it and use the Key from the Link as the name of the no-map book
          $this->links[$this->links_map]['title'] = $key;
        }
      } else if ($found['xtract']) {
// not included on the processing, stand alone links.
// echo '<span style="color:Tomato;">ignored <em>'.$idx.'</em> => <em>'.$key.'</em></span></br>';
      } else {
        $found_tags = false;
        foreach ($found as $k => $v) {
          if ($v) {
            $found_tags = true;
          }
        }
        if ($found_tags) {
// echo 'tags never found!<br/>';
        }
      }
// echo '</fieldset>';
    }
// $this->bookFolder($this->links);
  }

  private function bookFolder($array) {
    foreach ($array as $idx => $key) {
      if (is_array($key)) {
        $this->bookFolder($key);
      } else {
        echo $idx . '____' . $key . '<br/>';
      }
    }
  }

  // private function readDomArray($array, $node_name, $node_value) {
  //   foreach ($array as $idx => $key) {
  //     if (is_array($key)) {
  //       $this->readDomArray($key, $node_name, $node_value);
  //     } else {
  //       if( $idx == $node_name && $key == $node_value) {
  //         echo $idx . ' = ' . $key . '<br/>';
  //       } else {
  //         // echo '<em>ignore '.$key.'</em><br/>';
  //       }
  //     }
  //   }
  // }
  // private function readDomArray($array, $node_name, $node_value) {
  //   foreach ($array as $idx => $key) {
  //     if (is_array($key)) {
  //       $this->readDomArray($key, $node_name, $node_value);
  //     } else {
  //       if( $idx == $node_name && $key == $node_value) {
  //         echo $idx . ' = ' . $key . '<br/>';
  //       } else {
  //         // echo '<em>ignore '.$key.'</em><br/>';
  //       }
  //     }
  //   }
  // }

  private function indexHtmlTopNav($book_title = 'Interactive Scriptures App') {
//     return '<div class="topnav" id="myTopnav">
//    <table>
//      <tr>
//        <td class="leftButton">
//        </td>
//        <td class="titleBar">'
//        . $book_title
//        .'</td>
//        <td class="rightButton"> 
//        </td>
//      </tr>
//    </table>
//  </div>' . $this->new_line;
    return '';
  }

  private function indexHtmlNavStart($title = 'Scriptures') {
    return '<div class="content">
   <div class="title1">' . $title . '</div>
   <nav class="manifest">' . $this->new_line;
  }

  private function indexHtmlNavEnd() {
    return '</nav>
</div>' . $this->new_line;
  }

}

?>