<?php header('Content-Type: application/xhtml+xml'); ?><?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>formattie</title>
 </head>
 <body>
  <form action="/" method="POST">
   <a href="/" target="_blank">new tab</a><br/>
   <textarea id="content" name="content" rows="15" cols="80"><![CDATA[<?php
if (isset($_POST['content'])) {
    $_POST['content'] = ltrim($_POST['content']);
    echo $_POST['content'];
}
?>]]></textarea>
   <br/>
   <input type="button" value="Clear" onclick="javascript:document.getElementById('content').value='';"/>
   <input type="submit" value="Submit"/>
  </form>
<?php
    if (isset($_POST['content'])) {
        $content = $_POST['content'];
        if ($content{0} == '{' || $content{0} == '[') {
            //json
            $nice = json_encode(
                json_decode($content), JSON_PRETTY_PRINT
            );
            echo '<pre>' . htmlspecialchars($nice) . '</pre>';
        } else if (strpos(substr($content, 0, 64), ':{') !== false) {
            //serialized php variable
            $nice = var_export(unserialize($content), true);
        } else {
            //xml
            $descriptorspec = array(
                0 => array('pipe', 'r'),//stdin
                1 => array('pipe', 'w'),//stdout
                2 => array('pipe', 'w') //stderr
            );
            $process = proc_open('xmllint --format -', $descriptorspec, $pipes);
            if (!is_resource($process)) {
                die(
                    '<div class="alert alert-error">'
                    . 'Cannot open process to execute xmllint'
                    . '</div>'
                );
            }

            fwrite($pipes[0], $content);
            fclose($pipes[0]);

            $nice = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $retval = proc_close($process);

            if ($retval != 0) {
                echo '<div style="border:1px solid red;">Error> ' . htmlspecialchars($errors) . '</div>';
            }

            require_once 'MediaWiki/geshi/geshi/geshi.php';
            $geshi = new \GeSHi($nice, 'xml');
            //$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
            //$geshi->set_header_type(GESHI_HEADER_DIV);

            echo '<pre>' . $geshi->parse_code() . '</pre>';
            echo 'Size: ' . number_format(strlen($content) / 1024, 2) . 'kiB';
        }

        echo '<hr/>';
        echo '<textarea rows="10" cols="80"><![CDATA[' . $nice . ']]></textarea>';
    }
?>
  <script type="text/javascript">
    var content = document.getElementById('content');
    content.focus();
    if (content.value == '') {
        document.execCommand('paste');
    }
  </script>
 </body>
</html>
