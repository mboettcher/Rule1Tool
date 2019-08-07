<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

return array(
    // 'js' => array('//js/file1.js', '//js/file2.js'),
     'webcss' => array(
					     '../css/global.css', 
					     '../css/main.css', 
					     '../css/stock.css', 
					     '../css/analysis.css', 
					     '../css/thickbox.css', 
					     '../css/jquery.jgrowl.css', 
					     '../css/tablesorter/style.css', 
					     '../css/custom-theme/jquery-ui-1.7.2.custom.css', 
					     '../css/flexselect.css'
),
     'mobilecss' => array(
					     '../css/global.css', 
					     '../css/mobile.css',
					     '../css/tablesorter/style.css', 
					     '../css/custom-theme/jquery-ui-1.7.2.custom.css'
),

/**
 * 
 * 	<link rel="stylesheet" href="<?php echo $this->baseUrlShort();?>/public/css/global.css" type="text/css" media="screen, projection" />

	<link rel="stylesheet" href="<?php echo $this->baseUrlShort();?>/public/css/main.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?php echo $this->baseUrlShort();?>/public/css/stock.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?php echo $this->baseUrlShort();?>/public/css/analysis.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?php echo $this->baseUrlShort();?>/public/css/thickbox.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?php echo $this->baseUrlShort();?>/public/css/jquery.jgrowl.css" type="text/css"/>
	<link type="text/css" href="<?php echo $this->baseUrlShort();?>/public/css/tablesorter/style.css" rel="Stylesheet" />	
	<link type="text/css" href="<?php echo $this->baseUrlShort();?>/public/css/custom-theme/jquery-ui-1.7.2.custom.css" rel="Stylesheet" />
	<link type="text/css" href="<?php echo $this->baseUrlShort();?>/public/css/flexselect.css" rel="Stylesheet" />	
 * 
 * 
 */
'webjs' => array(
				//'../scripts/jquery-1.3.2.min.js', 
				'../scripts/r1dialog.js', 
				'../scripts/main.js', 
				'../scripts/stock.js', 
				'../scripts/analysis.js', 
				'../scripts/thickbox-uncompressed.js', 
				'../scripts/jquery.color.js', 
				'../scripts/jQuery.colorBlend.pack.js', 
				'../scripts/jquery.innerfade.js', 
				'../scripts/jquery.jgrowl_compressed.js', 
				'../scripts/jquery.tablesorter.min.js', 
				'../scripts/jquery.tooltip.v.1.1.js', 
				'../scripts/liquidmetal.min.js', 
				'../scripts/jquery.flexselect.js', 
		//		'../scripts/jquery-ui-1.7.2.custom.min.js'
),
'jquerybase' => array(
				'../scripts/jquery-1.3.2.min.js'
),
/**
 * 	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/r1dialog.js"></script>
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/main.js"></script>
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/stock.js"></script>
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/analysis.js"></script>
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/thickbox-uncompressed.js"></script> 
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.color.js"></script>
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jQuery.colorBlend.pack.js"></script> 
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.innerfade.js"></script> 
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.jgrowl_compressed.js"></script> 
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.tablesorter.min.js"></script> 
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.tooltip.v.1.1.js"></script> 

	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/liquidmetal.min.js"></script> 
	<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.flexselect.js"></script> 
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js"></script>

 *  * 
 */
'mobilejs' => array(
				//'../scripts/jquery-1.3.2.min.js', 
				'../scripts/jquery-ui-1.7.2.custom.min.mobile.js',
				'../scripts/jquery.iphone.min.js',
				'../scripts/mobile.js', 
				'../scripts/r1dialog.js', 
				'../scripts/jquery.tablesorter.min.js'
)

/**
 * 
 * <script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery-ui-1.7.2.custom.min.mobile.js"></script>
<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/mobile.js"></script>

<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/r1dialog.js"></script>
<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.iphone.min.js"></script>
<script type="text/javascript" src="<?php echo $this->baseUrlShort();?>/public/scripts/jquery.tablesorter.min.js"></script> 
 * 
 */

    // custom source example
    /*'js2' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => create_function('$a', 'return $a;')
        ))
    ),//*/

    /*'js3' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => array('Minify_Packer', 'minify')
        ))
    ),//*/
);