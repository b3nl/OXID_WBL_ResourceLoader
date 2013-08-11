OXID_WBL_ResourceLoader
=======================

PHP-Scripts for loading JS und CSS Files. The OXID-Functions oxscript and oxstyle are changed so that local files are collected and loaded through the css.php/js.php in the theme dir. 
The URLs for the css.php/js.php are called with the GET-Array "aWBLFiles" (the relative local paths for the resources) and the timestamp iWBLTimestamp. The Timestamp iWBLTimestamp contains the change timestamp of the last modified file and creates a unique URL for browser. This way allows that every css/js-URL can be cached forever, because oxscript/oxstyle create unique URLs for every file status.
The css.php/js.php check aWBLFiles and the timestamp and creates an matching ETag. If the browser sends a known ETag, which responds to the file array and the timestamp, than the php-Files respond with a "304 Not Modified"-Header and does nothing else. 
If the ETag is unkown or "wrong" the php-Files check for a rendered tmp-File (oxCache on EE, oxUtils-FileCache on PE/CE). If it exists, it is streamed to the browser. If not, the files from aWBLFiles are fetched and cached before streaming. 
After the rendering ist done, the ETag is sent to the browser and an expire-Header which is valid for a month.
