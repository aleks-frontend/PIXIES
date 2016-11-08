Version: $Id: README.txt,v 1.9 2001/11/11 14:04:39 ljp Exp $

README FOR JPGRAPH 1.4
======================

This ZIP contains JpGraph 1.4 an Object Oriented PHP4 Graph Plotting library.
The library is released under GPL 2.0

Included files
--------------
README.txt              This file
GPL.txt                 GPL 2.0  Licensee
changes.txt             Changelog for JpGraph
ToDo.txt						ToDo list for future releases
jpgraph.php             Base library
jpgraph_log.php         Extension to handle logarithmic scales
jpgraph_line.php        Extension to handle various types of line plots 
jpgraph_bar.php         Extension to handle various types of bar plots
jpgraph_error.php       Extension to handle various types of error plots
jpgraph_scatter.php     Extension to handle various types of scatter/impuls plots
jpgraph_spider.php      Extension to handle various types of spider plots
jpgraph_pie.php         Extension to handle various types of pie plots
jpgraph_canvas.php      Extension to handle a simple drawing canvas
jpgraph_pie3d.php			Extension to do 3D pie plots

gencolorchart.php       Unsupported utility to generate a sample color chart of all named colors
adjimg.php					Unsupported utility to change contrast/brightness for an image.

Requirements:
-------------
* PHP 4.02 or higher
* GD 1.8.x NOT GD 2.x

Installation
------------
0. Make sure your PHP is AT LEAST 4.02 and that you have compiled
   support for GD library. You must make aboslutely sure that you
   have GD working. Please run phpinfo() to check if GD library
   is supported in your installation. Please not that JpGraph only
   supports GD 1.x. If you use GD 2.x you are on your own. Among
   other thing it has been noted that JpGraph background images does not
   work with GD 2.x
   
1. Unzip and copy the files to a directory of your choice.

2. Set up the directory paths in jpgraph.php where the cache directory
   should be and where your TTF directory is. Note that Apache/PHP must
   have write permission in your cache directory. 
   
4. Check that all rest of the DEFINE in the top of JpGraph.php 
   is setup to your preference. The default should be fine
   for most usage.
   
3. Make sure PHP have write privileges to your cache directory.

4. Read the FAQ on http://www.aditus.nu/jpgraph/jpg_faq.php.

Potential problems:
------------------
1. Any PHP errors about function "imagecreate" does not exist indicates that
   your PHP installation does not include the GD library. This must be present.
2. Any error about "parent::" undefined means that you are not using PHP 4.02 or
   above. You _NEED_ 4.02 or higher.
3. If you don't get any background images you are most likely using GD 2.x which
   is not yet supported. JpGraph has only been verified with GD 1.x

Documentation
-------------
The latest documentation, both on-line, and off-line may be found at
http://www.aditus.nu/jpgraph/

Bug reports and suggestions
---------------------------
Should be sent to jpgraph@aditus.nu

Change history:
------------------------------------------------------------------------
Date        Ver      Comment
------------------------------------------------------------------------
2001-11-11  1.4 		Functional improvements, bug fixes.
2001-09-23  1.3.1		Minor bug fixes
2001-09-13  1.3      Major functional enhancements and minor bugfixes
2001-04-29  1.2.2    Minor bug fixes. Addded background image support 
2001-03-29  1.2.1    Minor bug fixes. Experimental support for 3D pie plots
2001-03-18  1.2      Second release see changes.txt
2001-02-18  1.1      Second release see changes.txt
2001-02-04  1.0      First public release

-------------------------------------------------------------------------

Stockholm/London , November 2001
Johan Persson (johanp@aditus.nu)

<EOF>