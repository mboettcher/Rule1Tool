<?php
/*
 * http://www.malaiac.com/GphpChart/
 */
class GphpChart 
  {
  var $chart;
  var $chart_url;
  var $base_url = "http://chart.apis.google.com/chart?";
  var $width = 700;
  var $height = 400;
  var $types = array ("lc","lxy","bhs","bvs","bhg","bvg","p","p3","v","s");
  var $chart_types = array('l' => 'line','b' => 'bar','p'=> 'pie','v' => 'venn','s' => 'scatter');
  var $mandatory_parameters = array('chs','chd','cht');
  var $data_prepared = false;
  var $allowed_parameters = array(
  'l' => array('chtt','chdl','chco','chf','chxt','chg','chm','chls','chxp'),
  'b' => array('chtt','chbh','chdl','chco','chf','chxt','chxp'),
  'p' => array('chtt','chco','chf','chl'),
  'v' => array('chtt','chdl','chco','chf'),
  's' => array('chtt','chdl','chco','chf','chxt','chg','chm','chxp'),
  );
  var $range = 1;
  var $encodings = array(
    's' => array('sep' => '','set' => ',','range' => 61,'missing' => '_'),
    't' => array('sep' => ',','set' => '|','range' => 100,'missing' => -1),
    'e' => array('sep' => '','set' => ',','range' => 4096,'missing' => '__'),
    );
  var $simple_encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  // min and max values of horizontal axis
  var $max_xt = 0;
  var $min_xt = 100000; // fake value to be sure we got the min data value
  // min and max values for vertical axis
  var $max_yr = 0;
  var $min_yr = 100000; // fake value to be sure we got the min data value
  
  var $max_yr_org = 0;
  var $min_yr_org = 100000;
  
  var $_ratiofakor = 0.9; //bewirkt, dass nur 90% der chartfläsche benutzt wird *must have*
  
  var $ratio = false;
  var $cached = true;
  var $prepared = false;
  
  		var $axis = null;
		var $axis_labels = array();
		var $pie_labels = array();
		var $label_positions = array();
		var $axis_ranges = array();
		var $axis_styles = array();
		var $rangemarkers = array();


function GphpChart($type = 'lc',$encoding = 't')
  {
  $this->chart = (object) NULL;
  // $chart = new stdClass();
  
  if(!in_array($type,$this->types)) return false;
  else $this->chart->cht = $type;
  $this->chart_type = $this->chart_types[substr($this->chart->cht,0,1)];
  
  if(!in_array($encoding,array_keys($this->encodings))) return false;
  else $this->encoding = $encoding;

  $this->sep = $this->encodings[$this->encoding]['sep'];
  $this->range = $this->encodings[$this->encoding]['range'];
  $this->missing = $this->encodings[$this->encoding]['missing'];
  $this->set = $this->encodings[$this->encoding]['set']; // set separator
  if($this->chart_type == 'venn') $this->set = ',';
  
  
  $string = $this->simple_encoding;
  unset($this->simple_encoding);
  for($i = 0;$i< strlen($string);$i++) $this->simple_encoding[] = $string[$i];
  
  $this->extended_encoding = $this->simple_encoding;
  $this->extended_encoding[] = '-'; $this->extended_encoding[] =   '.'; $this->extended_encoding[] =   '_'; $this->extended_encoding[] =   ',';
  }

  function setSize($width,$height)
  {
  	$this->width = $width;
  	$this->height = $height;
  }

  
/* PRE GENERATION : add labels, data, axis, etc */  

  function add_data($values,$color = '')
  {
  $this->cached = false;
  if($color != '' && strlen($color) == 6) 
  	$this->chart->chco[] = $color;
  	
	$tmpvalues = array();
	$count = count($values);
  	for($i=0;$i < $count; $i++)
  	{
  		//echo $i.":".$values[$i]." ";
  		if($values[$i] === null)
  		{
  			$tmpvalues[$i] = "__";	
  			unset($values[$i]);
  		}
  				
  	}
  //if($this->chart_type == 'scatter' && $n == 2) continue; // ignore min max values for plots sizes
  $this->max_yr = max($this->max_yr,max($values));
  //echo "<br><br>";
  $this->min_yr = min($this->min_yr,min($values));
 //echo $this->min_yr." ".$this->max_yr.".<br>";
 //print_r($values);
  $this->max_yr_org = $this->max_yr;
  $this->min_yr_org = $this->min_yr; 
      	
  
  $this->datas[] = array_merge($tmpvalues, $values); 
  
  } 
  
  function add_labels($axis,$values)
  {
  $this->cached = false;
  /*if(isset($values['values_type']))
  {
      $values_type = $values['values_type'];
      if($values_type == 'discret')
      {
        $min = $values['min']; $max = $values['max'];
        unset($values);
        for($i = $min; $i<=$max;$i++) $values[] = $i;
      }
  }*/
  
  // reverse order for Bar Horizontal 
  if($this->chart->cht == 'bhs' && is_string($values[0])) $values = array_combine(array_keys($values),array_reverse(array_values($values)));
  $this->labels[$axis][] = $values;

  if($axis == 'x' || $axis == 't') 
    {
    $this->max_xt = max($this->max_xt,max($values));
    $this->min_xt = min($this->min_xt,min($values));
    }
 
   // min and max values for vertical axis are calculated in prepare_data()  
  }
    
  function set_bar_width($width,$space = 0)
  {
  $this->cached = false;
  $this->chart->chbh = (int) $width;
  if($space != 0) $this->chart->chbh .= ','.$space; 
  }
  function fill($area,$type,$params)
  {
  $this->cached = false;
  $this->chart->chf[] = "$area,$type,$params";
  }
  function add_legend($array)
  {
  $this->cached = false;
      foreach($array as $legend)
      {
      	if($this->chart_type == 'pie') 
          	$this->chart->chl[] = $legend;
        else 
          	$this->chart->chdl[] = $legend;
      }

  }
  function setLegendPosition($string)
  {
  /*
   *  chdlp=b places the legend at the bottom
    * chdlp=t places the legend at the top
    * chdlp=r places the legend on the right
    * chdlp=l places the legend on the left
   * 
   */
  $this->cached = false;
	$this->chart->chdlp = $string;
  }
  function add_style($string)
  {
  $this->cached = false;
  if($this->chart_type == 'line') $this->chart->chls[] = $string;
  }
  function add_grid($string)
  {
  $this->cached = false;
  if($this->chart_type == 'line' || $this->chart_type == 'scatter') $this->chart->chg[] = $string;
  }
  function add_marker($string)
  {
  $this->cached = false;
  if($this->chart_type == 'line' || $this->chart_type == 'scatter') $this->chart->chm[] = $string;
  }
  function setChartMargins($array)
  {
  $this->cached = false;
  $this->chart->chma = $array;
  }
  
  public function setRangeMarkers($range)
  {
  /*
   * chm=
<r or R>,<color>,<any value>,<start point>,<end point>|
...
<r or R>,<color>,<any value>,<start point>,<end point>

<marker type>,<color>,<data set index>,<data point>,<size>,<priority>
   */
  if(!empty($range))
  	$this->rangemarkers[] = $range;
  	
  }
  public function setZeroLine()
  {
  /*
   * Erst aufrufen, wenn bereits alle Datensätze gesetzt
   */
  	if(($this->max_yr - $this->min_yr) != 0) //durch 0 teilt es sich so schwer
  		$pos = - $this->min_yr / ($this->max_yr - $this->min_yr);
  	else
  		$pos = 0;
  	
  	$this->setRangeMarkers(array("r","000000",0,$pos-0.002,$pos+0.002));
  }
  	public function setAxisRangeMaxMin($axis) {
			/*
				Description: Specify a range
				Usage: $graph->Graph->setAxisRange(array('axis index', 'start of range', 'end of range'));				
			*/
			if(!empty($axis)) 
				$this->axis_ranges[] = array("maxmin" => $axis);			
		}
		public function setAxisRange($ranges = array()) {
			/*
				Description: Specify a range
				Usage: $graph->Graph->setAxisRange(array('axis index', 'start of range', 'end of range'));				
			*/
			if(!empty($ranges)) 
				$this->axis_ranges[] = $ranges;			
		}
		
		public function setGridLines($x = 0, $y = 0, $line = 0, $blank = 0) {
			/*
				Description: Specify a chart grid
				Usage: $graph->Graph->setGridLines('x axis', 'y axis', 'length of line', 'length of blank');
				Notes: Parameter values can be integers or have a single decimal place - 10.0 or 10.5 for example.
			*/
			$this->grid_lines = null;
			if(!is_null($x)) $this->grid_lines .= $x.',';
			if(!is_null($y)) $this->grid_lines .= $y.',';
			if(!is_null($line)) $this->grid_lines .= $line.',';
			if(!is_null($blank)) $this->grid_lines .= $blank.',';
			$this->grid_lines = substr($this->grid_lines, 0, -1);
		}
		
		public function setLegend($legend = array()) {
			/*
				Description: Specify a legend for a chart
				Usage: $graph->Graph->setLegend(array('label', 'label', 'label', etc...));				
			*/
			$this->legend = $legend;			
		}
		
		public function addPieLabel($pie_labels = array()) {
			/*
				Description: Specify Pie labels
				Usage: $graph->Graph->addAxisLabel(array('label', 'label', 'label', etc...));
				Arguments:
					'label'				=	Label order corresponds with data order.
			*/
			if(!empty($pie_labels))
				$this->pie_labels = $pie_labels;
		}
		
		public function addAxisLabel($axis_labels = array()) {			
			/*
				Description: Specify Axis labels
				Usage: $graph->Graph->addAxisLabel(array('label', 'label', 'label', etc...));
				Arguments:
					'label'				=	The first label is placed at the start, the last at the end, others are uniformly spaced in between.
			*/
			if(!empty($axis_labels))
				$this->axis_labels[] = $axis_labels;
		}
		
		public function addAxisStyle($axis_styles = array()) {
			/*
				Description: Specify font size, color, and alignment for axis labels
				Usage: $graph->Graph->addAxisStyle(array('axis index', 'color', ['font size'], ['alignment']));
				Arguments:
					'axis index'			=	the axis index as specified
					'color'					=	the axis index as specified
					'font size'				=	is optional. If used this specifies the size in pixels.
					'alignment				=	is optional. By default: x-axis labels are centered, 
													left y-axis labels are right aligned, right y-axis labels are left aligned. 
													To specify alignment, use 0 for centered, -1 for left aligned, and 1 for right aligned.
			*/
			if(!empty($axis_styles))
				$this->axis_styles[] = $axis_styles;
		}
		
		public function setAxis($axes = array()) {			
			/*
				Description: Specify multiple axes
				Usage: $graph->Graph->setAxis(['bottom x-axis'], ['top x-axis'], ['left y-axis'], ['right y-axis']);
				Notes: Axes are specified by the index they have in the chxt parameter specification. 
							The first axis has an index of 0, the second has an index of 1, and so on. You can specify multiple axes by including x, t, y, or r multiple times.
			*/
			if(!empty($axes)) {
				$this->axis = null;
				foreach($axes as $axis) {
					$this->axis .= $axis.',';
				}
				$this->axis = substr($this->axis, 0, -1);				
			} else 
				$this->axis = 'x,t,y,r';			
		}
		
		public function addLabelPosition($label_positions = array()) {
			/*
				Description: Specify label positions
				Usage: $graph->Graph->addLabelPosition(array('position', 'position', 'position', etc...));
				Arguments:
					'position'				=	Use floating point numbers for position values.
			*/
			if(!empty($label_positions))
				$this->label_positions[] = $label_positions;
		}
		
  
/* END PRE GENERATION FUNCTIONS */

  



/* GENERATE FUNCTIONS : call prepare functions, prepare url, outputs url or full image string */

  function getChartArray()
  {
  	if(!$this->prepared) $this->prepare();
    return  (array) $this->chart;
  }
  function get_Image_URL()
  {
  if($this->cached) 
    {
    if(!$this->filename) $this->generate_filename();
    return $this->filename;
    }
  else
    {
    if(!$this->prepared) $this->prepare();
    return $this->chart_url;
    }  
  }
  function get_Image_String()
  {
  if($this->cached) 
    {
    if(!$this->filename) $this->generate_filename();
    $string = '<img alt="'.$this->title.'" src="'.$this->filename.'" />';
    }
  else  
    {
    if(!$this->prepared) $this->prepare();
    $string = '<img alt="'.$this->title.'" src="'.$this->chart_url.'" />';
    }
  return $string;
  }
  
  function getTitle()
  {
    if(!$this->prepared) $this->prepare();
    return $this->title;
   }
  function getWidth()
  {
    return $this->width;
   }
  function getHeight()
  {
    return $this->height;
   }
   
  function prepare()
  {
  if(!$this->data_prepared) $this->prepare_data();
  $this->prepare_labels();
  $this->prepare_title();
  $this->prepare_styles();
  $this->prepare_url();
  $this->prepared = true;
  }
/* END GENERATE FUNCTIONS */  
  

  /* CACHE FUNCTIONS */
  function generate_filename()
  {
  $this->filename = urlencode($this->title).'.png';
  }

  function save_Image()
  {
  if(!$this->filename) $this->generate_filename();
  /* get image file */
  //$this->chart_url = htmlspecialchars($this->chart_url);
  //$this->chart_url = urlencode($this->chart_url);
  
  if(    function_exists('file_get_contents')    && $this->image_content = file_get_contents($this->chart_url)    ) 
    $this->image_fetched = true;
    
  if(!$this->image_fetched)
    {
    if($fp = fopen($this->chart_url,'r'))
      {
      $this->image_content = fread($fp);
      fclose($fp);
      $this->image_fetched = true;
      }
    }
  
  /* write image to cache */
  if($this->image_fetched)
    {
    $fp = fopen($this->filename,'w+');  
    if($fp) 
      {
      fwrite($fp,$this->image_content);
      fclose($fp);
      }
    else { return false; }    
    }
  else { return false; }
  
  return true;
  }
  

/* PREPARE FUNCTIONS : called by generate functions, these ones parse labels and data */  
  function prepare_url()
  {
  $this->chart_url = $this->base_url;
  /*
  foreach($this->mandatory_parameters as $param)
  {
  if(!isset($this->chart->$param)) return false;
  $params[] = $param.'='.$this->chart->$param;
  }
  */
  foreach($this->chart as $k => $v)
    {
    if($v != '') $params[] = "$k=$v";
    }
  $this->chart_url .= implode('&',$params);
  }
  function prepare_styles()
  {
// SIZE
 
  if(($this->width * $this->height) > 300000) 
    {
    
    // reduces dimensions to match API limits ( 300mpixels )
    $size = $this->width * $this->height;
    $this->width = round($this->width * (300000 / $size),0);
    $this->height = round($this->height * (300000 / $size),0);
    }
  $this->chart->chs = $this->width.'x'.$this->height;

// colors
  if(isset($this->chart->chco) && is_array($this->chart->chco)) $this->chart->chco = implode(',',$this->chart->chco);
  if(isset($this->chart->chf) && is_array($this->chart->chf)) $this->chart->chf = implode('|',$this->chart->chf);

// styles
  if($this->chart_type == 'scatter' || $this->chart_type == 'line') 
    {
    if($this->chart_type == 'line') if(isset($this->chart->chls) && count($this->chart->chls)) $this->chart->chls = implode('|',$this->chart->chls);
    if(isset($this->chart->chg) && count($this->chart->chg)) $this->chart->chg = implode('|',$this->chart->chg);
// markers
     //if(isset($this->chart->chm) && count($this->chart->chm)) $this->chart->chm = implode('|',$this->chart->chm);
    			if(!empty($this->rangemarkers))
			{
				$this->chart->chm = "";
				foreach($this->rangemarkers as $range)
				{
						$this->chart->chm .= implode(',', $range).'|';
				}
					
				$this->chart->chm = substr($this->chart->chm, 0, -1);
			}
    }
//legend
  if(isset($this->chart->chl) && is_array($this->chart->chl)) $this->chart->chl = implode('|',$this->chart->chl);
  if(isset($this->chart->chdl) && is_array($this->chart->chdl)) $this->chart->chdl = implode('|',$this->chart->chdl);
 //ChartMargins
  if(isset($this->chart->chma) && is_array($this->chart->chma)) $this->chart->chma = implode(',',$this->chart->chma);
  
  }
  
  function prepare_size()
  {
  }
  
  function prepare_data()
  {
  // for lines charts, calculate ratio
  if($this->chart_type == 'line'  || $this->chart_type == 'bar' || $this->chart_type == 'scatter')
    {
   		//$this->normalizeData(); 
 
    	//info: (int) range ist die Anzahl der möglichen verschiedenen werte 
    	// min-yr kann positiv und negativ sein
    	$anzahlwerte = $this->max_yr-$this->min_yr;
    	if($anzahlwerte != 0) //durch 0 teilen geht wohl ned
    	{
    		//if($anzahlwerte > 1)
    			$this->ratio = $this->_ratiofakor * $this->range / $anzahlwerte;
    		//else 
    			//$this->ratio  = $this->_ratiofakor * $this->range * $anzahlwerte;
    		//echo $this->ratio." ".$this->_ratiofakor." ".$this->range." ".$anzahlwerte."<br>";
    	}  		
    	else
    		$this->ratio = $this->_ratiofakor;
    }
   
  foreach($this->datas as $n => $data)
    {
    if($this->chart_type == 'scatter' && $n == 2) $data = $this->encode_data($data,false); // do not normalize plots sizes
    else $data = $this->encode_data($data);
    
    if($this->chart->cht == 'lxy') 
      {
      $this->datas[$n] = implode($this->sep,array_keys($data)).'|'.implode($this->sep,array_values($data));
      }
    else $this->datas[$n] = implode($this->sep,$data);
    }
  
  $this->chart->chd = "$this->encoding:";
  $this->chart->chd .= implode($this->set,$this->datas);
  $this->data_prepared = true;
  }
  
  
  function prepare_labels()
  {
  //chxt= axis titles
  //chxl= set:labels
  //chxr= range
  //chxp= positions
  /*
   * if(count($this->chart->chxr)) $this->chart->chxr = implode('|',$this->chart->chxr);
  if(count($this->chart->chxp)) $this->chart->chxp = implode('|',$this->chart->chxp);    
  if(count($this->chart->chxt)) $this->chart->chxt = implode(',',$this->chart->chxt);
  if(count($this->chart->chxl)) $this->chart->chxl = implode('|',$this->chart->chxl);
   */
  			if(!empty($this->axis)) {
				$this->chart->chxt = $this->axis;
			}
			if(!empty($this->axis_labels)) {	
				$labelCount = 0;
				$this->chart->chxl = "";
				foreach($this->axis_labels as $value) {
					if(!empty($value)) {						
						if($labelCount) {
							$this->chart->chxl .= '|';
						}
						$this->chart->chxl .= $labelCount.':|'.implode('|', $value);						
						$labelCount++;
					} else
						$labelCount++;										
				}
			}  
  
  			if(!empty($this->label_positions)) {
				$this->chart->chxp = "";
  				foreach($this->label_positions as $position) {
					$this->chart->chxp .= implode(',', $position).'|';
				}
				$this->chart->chxp = substr($this->chart->chxp, 0, -1);
			}
			if(!empty($this->axis_ranges)) {
				$this->chart->chxr = "";
				foreach($this->axis_ranges as $ranges)
				{
					
					if(isset($ranges["maxmin"]))
					{
						$maxmindiff = $this->max_yr_org - $this->min_yr_org; // = 110%
						$max_yr_new = $this->max_yr_org+($maxmindiff*1.11111-$maxmindiff);
						$min_yr_new = $this->min_yr_org;
						
						$this->chart->chxr .= $ranges["maxmin"].",".$min_yr_new.",".$max_yr_new.'|';
					}
					else 
						$this->chart->chxr .= implode(',', $ranges).'|';
				}
					
				$this->chart->chxr = substr($this->chart->chxr, 0, -1);
			}
			if(!empty($this->axis_styles)) {				
				$this->chart->chxs = '';
				foreach($this->axis_styles as $axis_style) {
					$this->chart->chxs .= str_replace('#', '', implode(',', $axis_style).'|');
				}
				$this->chart->chxs = substr($this->chart->chxs, 0, -1);
			}

  
  
  /*
  $n = 0;
  if(count($this->labels))
  foreach($this->labels as $axis => $labelles)
    {
    foreach($labelles as $pos => $labels)
      {
      // axis type
      $this->chart->chxt[$n] = $axis;
      if(!count($labels)) continue; // no values = "neither positions nor labels. The Chart API therefore assumes a range of 0 to 100 and spaces the values evenly."
      // axis range
      
      if($this->chart_type == 'line'  || $this->chart_type == 'bar')
      {
      if($axis == 'x' || $axis == 't') 
        { 
        if($this->max_xt) $this->chart->chxr[$n] = $n.','.$this->min_xt.','.$this->max_xt; 
        }
      else  
        {
        if($this->max_yr) $this->chart->chxr[$n] = $n.','.$this->min_yr.','.$this->max_yr;
        }
      }
      
      // axis labels
      $this->chart->chxl[$n] = "$n:|".implode('|',$labels);
      if($this->chart_type == 'line' || $this->chart_type == 'bar' || $this->chart_type == 'scatter')
        {
        if(array_slice(array_keys($labels),0,2) != array(0,1))  $this->chart->chxp[$n] = "$n,".implode(',',array_keys($labels));
        else $this->chart->chxp[$n] = "$n,".implode(',',array_values($labels));
        }
      $n++;         
      }
    }
  if(count($this->chart->chxr)) $this->chart->chxr = implode('|',$this->chart->chxr);
  if(count($this->chart->chxp)) $this->chart->chxp = implode('|',$this->chart->chxp);    
  if(count($this->chart->chxt)) $this->chart->chxt = implode(',',$this->chart->chxt);
  if(count($this->chart->chxl)) $this->chart->chxl = implode('|',$this->chart->chxl);
  */
  }
  function prepare_title()
  {
  //chtt=first+line|second+line
  $this->chart->chtt = str_replace(array("\n","\n\r",'<br />','<br>'),'|',$this->title);
  //$this->chart->chtt = str_replace(' ','+',$this->chart->chtt);
  //$this->chart->chtt = str_replace('&','',$this->chart->chtt);
  }
  
/* END PREPARE FUNCTIONS */
  
/* ENCODING */
  function encode_data($data,$ratio = true)
  {
  if($this->encoding == 's')
    {
    foreach($data as $n => $value)
      {
      if(empty($value) || $value == '') $data[$n] = $this->missing;
      else $data[$n] = $this->simple_encoding[$value];
      }
    }
  elseif($this->encoding == 't')
    {
    foreach($data as $n => $value)
      {
      
      if(empty($value) || $value == '') $data[$n] = $this->missing; 
      elseif($ratio && $this->ratio) $data[$n] = (float) round($value * $this->ratio,1);
      else $data[$n] = (float) $value;
      }
    }
  elseif($this->encoding == 'e')
    {
    	if($this->min_yr < 0) //wenn negative werte vorhanden, dann soll auch nach unten ein abstand sein
        {
        	$abstandunten = round($this->min_yr * $this->ratio*1.111,0) - round($this->min_yr * $this->ratio,0);
        	//echo $abstandunten."<br>";
        }
        else 
        	$abstandunten = 0;
	        	
	    foreach($data as $n => $value)
	    {
	      if($value === null || $value === false || $value === '' || $value === '__') 
	      {
	      	//echo "asaddsa".$value;
	      	$data[$n] = $this->missing;
	      }
	      else
	      {
	       //echo "DEBUG: ".$value." . ".$this->min_yr." . ".$this->max_yr."<br>";      
	      	
	        // ratio anwenden
	        if($ratio && $this->ratio) {
	        	$value = round($value * $this->ratio,0);
	        }
	        //normalize - über 0 schieben
	       //echo "DEBUG: ".$value." ".round($this->min_yr * $this->ratio,0)."<br>";      
	        $value = $value - round($this->min_yr * $this->ratio + $abstandunten,0);
	        // encode
	        $value = $this->extended_encode($value);
	        $data[$n] = $value;
	       }
	   	}
    }
  return $data;
  }  
  
  function extended_encode($value)
  {
  $first = floor($value / 64);
  $second = $value - ($first * 64);
  //echo $first. " ". $second."<br>";
  if($first < 0)
  	$first = -$first;
  //echo $value." ".$this->ratio." ".$first." ".$this->title."<br>";
  $first = $this->extended_encoding[$first];
  $second = $this->extended_encoding[$second];
  return $first.$second;
  }
  
  function normalizeData()
  {
  	//alle daten positiv machen und an 0 ausrichten
  	foreach($this->datas as $n => $data)
  	{
      foreach($data as $nn => $value)
      {
 	    $value = $value - $this->min_yr;
        
 	    $data[$nn] = $value;
      }
      $this->datas[$n] = $data;
  	}
	//$this->max_yr = $this->max_yr - $this->min_yr;
    //$this->min_yr = $this->min_yr - $this->min_yr;
  }
  
  }

?>
