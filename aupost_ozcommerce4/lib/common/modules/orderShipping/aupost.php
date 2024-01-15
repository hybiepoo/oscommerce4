<?php

/***************************************************************
Australia Post shipping estimator for oscommerce 4

author: hybiepoo@hotmail.com
credits: This module is a port of the zencart aupost module:
https://www.zen-cart.com/downloads.php?do=file&id=1138

I have only modified it where necessary to work with osc4

***************************************************************/

/**
 * namespace
 */

namespace common\modules\orderShipping;

/**
 * used classes
 */
use common\classes\modules\ModuleShipping;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

// BMHDEBUG switches
define('APDEBUG1','No'); // No or Yes // BMH 2nd level debug to display all returned data from Aus Post
define('APDEBUG2','No'); // No or Yes // BMH 3nd level debug to display all returned XML data from Aus Post

// **********************

//BMH declare constants
//BMHif (!defined('MODULE_SHIPPING_AUPOST_TAX_CLASS')) { define('MODULE_SHIPPING_AUPOST_TAX_CLASS',''); }
if (!defined('MODULE_SHIPPING_AUPOST_TYPES1')) { define('MODULE_SHIPPING_AUPOST_TYPES1',''); }
if (!defined('MODULE_SHIPPING_AUPOST_TYPE_LETTERS')) { define('MODULE_SHIPPING_AUPOST_TYPE_LETTERS',''); }
if (!defined('MODULE_SHIPPING_AUPOST_HIDE_PARCEL')) { define('MODULE_SHIPPING_AUPOST_HIDE_PARCEL',''); }
if (!defined('MODULE_SHIPPING_AUPOST_CORE_WEIGHT')) { define('MODULE_SHIPPING_AUPOST_CORE_WEIGHT',''); }
if (!defined('MODULE_SHIPPING_AUPOST_STATUS')) { define('MODULE_SHIPPING_AUPOST_STATUS',''); }
if (!defined('MODULE_SHIPPING_AUPOST_SORT_ORDER')) { define('MODULE_SHIPPING_AUPOST_SORT_ORDER',''); }
if (!defined('MODULE_SHIPPING_AUPOST_ICONS')) { define('MODULE_SHIPPING_AUPOST_ICONS',''); }
if (!defined('MODULE_SHIPPING_AUPOST_TAX_BASIS')) {define('MODULE_SHIPPING_AUPOST_TAX_BASIS', 'Shipping');}
if (!defined('VERSION_AU')) { define('VERSION_AU', '1.0.0.0');}
// +++++++++++++++++++++++++++++
define('AUPOST_MODE','PROD'); //Test OR PROD    // Test uses test URL and Test Authkey;
                                                // PROD uses the key input via the admin shipping modules panel for "Australia Post"
// **********************

// ++++++++++++++++++++++++++
if (!defined('MODULE_SHIPPING_AUPOST_AUTHKEY')) { define('MODULE_SHIPPING_AUPOST_AUTHKEY','') ;}
if (!defined('AUPOST_TESTMODE_AUTHKEY')) { define('AUPOST_TESTMODE_AUTHKEY','28744ed5982391881611cca6cf5c240') ;} // DO NOT CHANGE
if (!defined('AUPOST_URL_TEST')) { define('AUPOST_URL_TEST','test.npe.auspost.com.au'); }       // No longer used - leave as prod url
if (!defined('AUPOST_URL_PROD')) { define('AUPOST_URL_PROD','digitalapi.auspost.com.au'); }
if (!defined('LETTER_URL_STRING')) { define('LETTER_URL_STRING','/postage/letter/domestic/service.xml?'); } //
if (!defined('LETTER_URL_STRING_CALC')) { define('LETTER_URL_STRING_CALC','/postage/letter/domestic/calculate.xml?'); } //
if (!defined('PARCEL_URL_STRING')) { define('PARCEL_URL_STRING','/postage/parcel/domestic/service.xml?from_postcode='); } //
if (!defined('PARCEL_URL_STRING_CALC')) { define('PARCEL_URL_STRING_CALC','/postage/parcel/domestic/calculate.xml?from_postcode='); }//

// set product variables
$aupost_url_string = AUPOST_URL_PROD ;
$aupost_url_apiKey = MODULE_SHIPPING_AUPOST_AUTHKEY;
$lettersize = 0;    //set flag for letters

// set product variables
$aupost_url_string = AUPOST_URL_PROD ;
$aupost_url_apiKey = MODULE_SHIPPING_AUPOST_AUTHKEY;
$lettersize = 0;    //set flag for letters
$currencies = \Yii::$container->get('currencies');

/**
 * class declaration
 */
class aupost extends ModuleShipping {

    /**
     * variables
     */
	public $allowed_methods;    //
    public $allowed_methods_l;  //
    public $FlatText;           //
    public $aus_rate;           //
    public $_check;             //
    public $code;			    // Declare shipping module alias code
    public $description;        // Shipping module display description
    public $dest_country;       // destination country
    public $dim_query;          //
    public $dims;               //
    public $enabled;		    // Shipping module status
    public $frompcode;          // source post code
    public $icon;               // Shipping module icon filename/path
    public $item_cube;          // cubic volume of item
    public $logo;               // au post logo
    public $myarray = [];       //
    public $myorder;            //
    public $ordervalue;         // value of order
    public $parcel_cube;        // cubic volume of parcel // NOT USED YET
    public $producttitle;       //
    public $quotes =[];         //
    public $sort_order;         // sort order for quotes options
    public $tax_basis;          //
    public $tax_class;          //
    public $testmethod;         //
    public $title;              //
    public $topcode;            //
    public $usemod;             //
    public $usetitle;           //
	public $currencies;

	/**
     * default values for translation
     */
    protected $defaultTranslationArray = [
        'MODULE_AUPOST_TEXT_TITLE' => 'Australia Post Rates',
        'MODULE_AUPOST_TEXT_DESCRIPTION' => 'Australia Post Shipping Module',
		'MODULE_SHIPPING_AUPOST_TEXT_ERROR' => '<font color="#FF0000">Estimate only:</font> We were unable to obtain a valid quote from the Australia Post Server.<br />You may still checkout using this method or contact us for accurate postage costs.',
		'MSGLETTERTRACKING' => ' <b>(No tracking)</b>',
		'MODULE_SHIPPING_AUPOST_SORT_ORDER' => '0',
    ];
	
    /**
     * class constructor
     */
    function __construct() {
		parent::__construct();
		global $db, $template , $tax_basis;
		$this->code = 'aupost';
        $this->title = MODULE_AUPOST_TEXT_TITLE;
        $this->description = MODULE_AUPOST_TEXT_DESCRIPTION . ' V'. VERSION_AU;;
		$this->online = true;
        $this->sort_order = '0';
        $this->icon = '';
        $this->logo = '';
		$this->tax_class = defined('MODULE_SHIPPING_AUPOST_TAX_CLASS') && MODULE_SHIPPING_AUPOST_TAX_CLASS;
        $this->sort_order = MODULE_SHIPPING_AUPOST_SORT_ORDER;
		//$this->tax_basis = defined('MODULE_SHIPPING_AUPOST_TAX_BASIS') && MODULE_SHIPPING_AUPOST_TAX_BASIS;
		 $this->tax_basis = 'Shipping' ;
		if (MODULE_SHIPPING_AUPOST_ICONS != "No" ) {
            $this->logo = DIR_WS_ICONS . 'aupost_logo.jpg';
            $this->icon = $this->logo;                  // set the quote icon to the logo //BMH DEBUG
			//if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title, 50, 50);
			//$this->quotes['icon'] = tep_image($this->icon, $this->title, 50, 50);
        }
		$this->enabled = (defined('MODULE_SHIPPING_AUPOST_STATUS') && (MODULE_SHIPPING_AUPOST_STATUS == 'True'));
		// get letter and parcel methods defined
        $this->allowed_methods_l = explode(", ", MODULE_SHIPPING_AUPOST_TYPE_LETTERS); // BMH
        $this->allowed_methods = explode(", ", MODULE_SHIPPING_AUPOST_TYPES1) ;
        $this->allowed_methods = $this->allowed_methods + $this->allowed_methods_l;  // BMH combine letters + parcels into one methods list
    }

    /**
     * you custom methods
     */
    function quote($method = '') {
		global $db, $order, $cart, $currencies, $template, $parcelweight, $packageitems;
		$currencies = \Yii::$container->get('currencies');
		$order = $this->manager->getOrderInstance();
		//print_r($cart->get_products());
		//print_r($cart);
        $methods = [];
		/// Single Quote ///
        if (tep_not_null($method) && (isset($_SESSION['aupostQuotes']))) {
            $testmethod = $_SESSION['aupostQuotes']['methods'] ;

            foreach($testmethod as $temp) {
                $search = array_search("$method", $temp) ;
                if (strlen($search) > 0 && $search >= 0) break ;
            }

            $usemod = $this->title ;
            $usetitle = $temp['title'] ;
            if (MODULE_SHIPPING_AUPOST_ICONS != "No" ) {  // strip the icons //
                if (preg_match('/(title)=("[^"]*")/',$this->title, $module))  $usemod = trim($module[2], "\"") ;
                if (preg_match('/(title)=("[^"]*")/',$temp['title'], $title)) $usetitle = trim($title[2], "\"") ;
            }

            //  Initialise our quote array(s)  // quotes['id'] required in includes/classes/shipping.php
            // reset quotes['id'] as it is mandatory for shipping.php but not used anywhere else
            $this->quotes = [
                'id' => $this->code,
                'module' => $usemod,
                'methods' => [
                [
                'id' => $method,
                'title' => $usetitle,
                'cost' =>  $temp['cost']
                ]
              ]
            ];

            if ($this->tax_class > 0) {
				$this->quotes['tax'] = \common\helpers\Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'] ?? null, $this->delivery['zone_id'] ?? null);
			}

			if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title, 80,60, 'style="padding: 0px 0px 0px 20px;"');
			
		}  ///  Single Quote Exit Point ////
		
		/// LETTERS - values  ///
        if (MODULE_SHIPPING_AUPOST_TYPE_LETTERS  <> null) {

            $MAXLETTERFOLDSIZE = 15;                        // mm for edge of envelope
            $MAXLETTERPACKINGDIM = 4;                       // mm thickness of packing. Letter max height is 20mm including packing
            $MAXWEIGHT_L = 500 ;                            // 500g
            $MAXLENGTH_L = (360 - $MAXLETTERFOLDSIZE);      // 360mm max letter length  less fold size on edges
            $MAXWIDTH_L =  (260 - $MAXLETTERFOLDSIZE);      // 260mm max letter width  less fold size on edges
            $MAXHEIGHT_L = (20 - $MAXLETTERPACKINGDIM);     // 20mm max letter height LESS packing thickness
            $MAXHEIGHT_L_SM = 5;                            // 5mm max small letter height
            $MAXLENGTH_L_SM = (240 - $MAXLETTERFOLDSIZE);   // 240mm
            $MAXWIDTH_L_SM = (130 - $MAXLETTERFOLDSIZE);    // 130mm
            $MAXWEIGHT_L_WT1 = 125;                         // weight 125
            $MAXWEIGHT_L_WT2 = 250;                         //
            $MAXWEIGHT_L_WT3 = 500;                         //
            $MSGLETTERTRACKING = MSGLETTERTRACKING;         // label append formatted in language file
            $MAXWIDTH_L_SM_EXP = 110;                       // DL envelope prepaid Express envelopes
            $MAXLENGTH_L_SM_EXP = 220;                      // DL envelope prepaid Express envelopes
            $MAXWIDTH_L_MED_EXP = 162;                      // C5 envelope prepaid Express envelopes
            $MAXLENGTH_L_MED_EXP = 229;                     // C5 envelope prepaid Express envelopes
            $MAXWIDTH_L_LRG_EXP = 250;                      // B4 envelope prepaid Express envelopes
            $MAXLENGTH_L_LRG_EXP = 353;                     // B4 envelope prepaid Express envelopes
            $MINVALUEEXTRACOVER = 101;                      // Aust Post amount for min insurance charge

            // initialise variables
            $letterwidth = 0 ;
            $letterwidthcheck = 0 ;
            $letterwidthchecksmall = 0 ;
            $letterlength = 0 ;
            $letterlengthcheck = 0 ;
            $letterlengthchecksmall = 0 ;
            $letterheight = 0 ;
            $letterheightcheck = 0 ;
            $letterheightchecksmall = 0 ;
            $letterweight = 0 ;
            $lettercube = 0 ;
            $letterchecksmall = 0 ;
            $lettercheck = 0 ;
            $lettersmall = 0;
            $letterlargewt1 = 0;
            $letterlargewt2 = 0;
            $letterlargewt3 = 0;
            $letterexp_small = 0;
            $letterexp_med = 0;
            $letterexp_lrg = 0;
            $letterprefix = 'LETTER ';               // prefix label to differentiate from parcel - include space

        }
        // EOF LETTERS - values
		
		// PARCELS - values
        // Maximums - parcels
        $MAXWEIGHT_P = 22 ;     // BMH change from 20 to 22kg 2021-10-07
        $MAXLENGTH_P = 105 ;    // 105cm max parcel length
        $MAXGIRTH_P =  140 ;    // 140cm max parcel girth  ( (width + height) * 2)
        $MAXCUBIC_P = 0.25 ;    // 0.25 cubic meters max dimensions (width * height * length)

        // default dimensions   // parcels
        $x = explode(',', MODULE_SHIPPING_AUPOST_DIMS) ;
        $defaultdims = array($x[0],$x[1],$x[2]) ;
        sort($defaultdims) ;    // length[2]. width[1], height=[0]

        // initialise  variables // parcels
        $parcelwidth = 0 ;
        $parcellength = 0 ;
        $parcelheight = 0 ;
        $parcelweight = 0 ;
        $cube = 0 ;
        $details = ' ';
        $item_cube = 0;
        $parcel_cube = 0;  // NOT USED YET

        $frompcode = defined(MODULE_SHIPPING_AUPOST_SPCODE);
        $dest_country=($this->delivery['country']['iso_code_2'] ?? '');    //
		//echo "Destination Country: " . $dest_country . PHP_EOL;
        $topcode = str_replace(" ","",($this->delivery['postcode'] ?? ''));
		//echo "Postcode: " . $topcode . PHP_EOL;
        $aus_rate = (float)$currencies->get_value('AUD') ;                  // get $AU exchange rate
		//echo "AUD: " . $currencies->get_value('AUD') ;
        // EOF PARCELS - values
	
		//$ordervalue = $_SESSION['cart']->total / $ausrate;
		//print_r($cart);
		//print_r($_SESSION);
        $tare = MODULE_SHIPPING_AUPOST_TARE ;                           // percentage to add for packing etc

        if (($topcode == "") && ($dest_country == "AU")) {
			return;
		}                                       //  This will occur with guest user first quote where no postcode is available

        // Only proceed for AU addresses
        if ($dest_country != "AU") {            //BMH There are no quotes
           return;                              //BMH  exit as overseas post is a separate module
        }

       // loop through cart extracting productIDs and qtys //
        $myorder = $cart->get_products();
		//
		if ($aus_rate == 0) {                                               // included to avoid possible divide  by zero error
            $aus_rate = (float)$currencies->get_value('AUS') ;              // if AUD zero/undefined then try AUS // BMH quotes added
            if ($aus_rate == 0) {
                $aus_rate = 1;                                              // if still zero initialise to 1.00 to avoid divide by zero error
            }
        }
		$ordervalue = 0;
		//print_r($myorder);
		if (MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) {
			echo "Printing $myorder";
			print_r($myorder);
		}
        for($x = 0 ; $x < count($myorder) ; $x++ ) {
            $producttitle = $myorder[$x]['id'] ;
            $q = $myorder[$x]['quantity'];
            $w = $myorder[$x]['weight'];
			$p = $myorder[$x]['price'];
			$ordervalue = $ordervalue + ($p * $q);                           // total cost for insurance
			//echo $ordervalue;

            $dim_query = tep_db_query("select length_cm, height_cm, width_cm from " . TABLE_PRODUCTS . " where products_id='$producttitle' limit 1 ");
            $dims = tep_db_fetch_array($dim_query);
            // re-orientate //
            $var = array($dims['width_cm'], $dims['height_cm'], $dims['length_cm']) ; sort($var) ;
			if (MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) {
				echo "producttitle = " . $producttitle . PHP_EOL;
				print_r($dims);
				print_r($var);
				echo PHP_EOL;
			}
            $dims['length_cm'] = $var[2] ; 
			$dims['width_cm'] = $var[1] ;  
			$dims['height_cm'] = $var[0] ;

            // if no dimensions provided use the defaults
            if($dims['height_cm'] == 0) {$dims['height_cm'] = $defaultdims[0] ; }
            if($dims['width_cm']  == 0) {$dims['width_cm']  = $defaultdims[1] ; }
            if($dims['length_cm'] == 0) {$dims['length_cm'] = $defaultdims[2] ; }
            if($w == 0) {$w = 1 ; }  // 1 gram minimum

            $parcelweight += $w * $q;

            // get the cube of these items
            $itemcube =  ($dims['width_cm'] * $dims['height_cm'] * $dims['length_cm'] * $q *0.000001 ) ; // item dims must be in cm
            // Increase widths and length of parcel as needed
            if ($dims['width_cm'] >  $parcelwidth)  { $parcelwidth  = $dims['width_cm']  ; }
            if ($dims['length_cm'] > $parcellength) { $parcellength = $dims['length_cm'] ; }
            // Stack on top on existing items
            $parcelheight =  ($dims['height_cm'] * ($q)) + $parcelheight  ;
            $packageitems =  $packageitems + $q ;

            // Useful debugging information // in formatted table display
           if (MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) {
                $dim_query = tep_db_query("select products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id='$producttitle' limit 1 ");
                $name = tep_db_fetch_array($dim_query);

                echo "<center><table class=\"aupost-debug-table\" border=1><th colspan=8> Debugging information ln329 [aupost Flag set in Admin console | shipping | aupost]</hr>
                <tr><th>Item " . ($x + 1) . "</th><td colspan=7>" . $name->fields['products_name'] . "</td>
                <tr><th width=1%>Attribute</th><th colspan=3>Item</th><th colspan=4>Parcel</th></tr>
                <tr><th>Qty</th><td>&nbsp; " . $q . "<th>Weight</th><td>&nbsp; " . $w . "</td>
                <th>Qty</th><td>&nbsp;$packageitems</td><th>Weight</th><td>&nbsp;" ; echo $parcelweight + (($parcelweight* $tare)/100) ; echo " " .  MODULE_SHIPPING_AUPOST_WEIGHT_FORMAT . "</td></tr>
                <tr><th>Dimensions</th><td colspan=3>&nbsp; " . $dims['length_cm'] . " x " . $dims['width_cm'] . " x "  . $dims['height_cm'] . "</td>
                <td colspan=4>&nbsp;$parcellength  x  $parcelwidth  x $parcelheight </td></tr>
                <tr><th>Cube</th><td colspan=3>&nbsp; itemcube=" . ($itemcube ) . " cubic vol" . "</td><td colspan=4>&nbsp;" . ($itemcube ) .  " cubic vol" . " </td></tr>
                <tr><th>CubicWeight</th><td colspan=3>&nbsp;" . ($itemcube *  250) . "Kgs  </td><td colspan=4>&nbsp;"
                    . ($itemcube  * 250) . "Kgs </td></tr>
                </table></center> " ;
                // NOTE: The chargeable weight is the greater of the physical or the cubic weight of your parcel
            } // eof debug display table
        }
		if ($ordervalue != "0" && $aus_rate != "0") {
			$ordervalue = $ordervalue / $aus_rate;
		}
		
		// /////////////////////// LETTERS //////////////////////////////////
        // BMH for letter dimensions
        // letter height for starters
        $letterheight = $parcelheight *10;                      // letters are in mm
        $letterheight = $letterheight + $MAXLETTERPACKINGDIM;   // add packaging thickness to letter height

        if (($letterheight ) <= $MAXHEIGHT_L ){
            $letterheightcheck = 1;                             // maybe can be sent as letter by height limit
            $lettercheck = 1;
            // check letter height small
            if (($letterheight) <= $MAXHEIGHT_L_SM ) {
                $letterheightchecksmall = 1;
                $letterchecksmall = 1;                          // BMH DEBUG echo '<br> ln331 $letterlengthcheckSmall=' . $letterlengthcheckSmall;
            }

            // letter length in range for small
            $letterlength = ($parcellength *10);
            if ($letterlength < $MAXLENGTH_L_SM ) {
                $letterlengthchecksmall = 1;
                $letterchecksmall = $letterchecksmall + 1;
            }

            // check letter length in range
            if (($letterlength  > $MAXLENGTH_L_SM ) || ($letterlength <= $MAXLENGTH_L ) ) {
                $letterlengthcheck = 1;
                $lettercheck = $lettercheck + 1;
            }
            // letter width in range
            $letterwidth = $parcelwidth *10;
            if ($letterwidth < $MAXWIDTH_L_SM ) {
                $letterwidthchecksmall = 1;
                $letterchecksmall = $letterchecksmall + 1;
            }

            if (($letterwidth > $MAXWIDTH_L_SM ) || (($parcelwidth *10) <= $MAXWIDTH_L) ) {
                $letterwidthcheck = 1;
                $lettercheck = $lettercheck + 1;
            }

            // check letter weight // in grams
            $letterweight = ($parcelweight + ($parcelweight* $tare/100));
            if ((($letterweight ) <= $MAXWEIGHT_L_WT1 ) && ($letterchecksmall == 3) ){
                $lettersmall = 1;
            }
            if ((($letterweight ) <= $MAXWEIGHT_L_WT1 ) && ($lettercheck == 3) ) {
                $letterlargewt1 = 1;
            }
            if  (($letterweight  >= $MAXWEIGHT_L_WT1 ) && ($letterweight <= $MAXWEIGHT_L_WT2 ) && ($lettercheck == 3)  ) {
                $letterlargewt2 = 1;
            }

            // BMH DEBUG2 display the letter values ';
            if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                 echo ' <br> aupost ln400 $lettercheck=' . $lettercheck . ' $letterchecksmall=' . $letterchecksmall . ' $letterlengthcheck = ' . $letterlengthcheck . ' $letterwidthcheck = ' . $letterwidthcheck . ' $letterheightcheck=' . $letterheightcheck;
                if ($letterchecksmall == 3) {
                    echo ' <br> ln402 it is a  small letter';
                    if ($lettercheck == 3) {
                        echo ' <br> ln404 it is a  large letter';
                    }
                    if ($letterlargewt1 == 1){
                        echo ' <br> ln407 it is a  large letter(125g)';
                    }
                    if ($letterlargewt2 == 1){
                        echo ' <br> ln 410 it is a  large letter(250g)';
                    }
                   if ($letterlargewt3 == 1){
                        echo ' <br> ln413 it is a  large letter(500g)';
                    }
                }
            } // BMH DEBUG2 eof display the letter values ';

            $aupost_url_string = AUPOST_URL_PROD;

            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                echo '<br>aupost line 404 ' .'https://' . $aupost_url_string . LETTER_URL_STRING . "length=$letterlength&width=$letterwidth&thickness=$letterheight&weight=$letterweight" ;
            } // eof debug URL

            // +++++++++++++++++ get the letter quote +++++++++++++++++++
            // letter quote request is different format to parcel quote request
            $quL = $this->get_auspost_api(
                'https://' . $aupost_url_string . LETTER_URL_STRING . "length=$letterlength&width=$letterwidth&thickness=$letterheight&weight=$letterweight") ;

            // If we have any results, parse them into an array
			if (!class_exists('SimpleXMLElement')) {
				\Yii::error(print_r(\Yii::$app->request->post(), 1), "SimpleXMLElement class not found");
				exit;
			} else {
				$xmlquote_letter = ($quL == '') ? array() : new \SimpleXMLElement($quL)  ;
			}

            //  bof XML formatted output
            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                echo "<p class=\"aupost-debug\"><strong>>> Server Returned - LETTERS APDEBUG1+2 line 417 << <br> </strong><textarea > " ;
                print_r($xmlquote_letter) ;         //  BMH DEBUG
                echo "</textarea></p>" ;
            } //eof debug server return

            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                echo "<table class=\"aupost-debug\"  ><tr><td><b>auPost - Server Returned APDEBUG2 ln445 LETTERS: output \$quL</b><br>" . $quL . "</td></tr></table>" ;
            } // BMH DEBUG eof XML formatted output

            // ======================================
            //  loop through the LETTER quotes retrieved //
            // create array
            $arrayquotes = array( array("qid" => "","qcost" => 0,"qdescription" => "") );

            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                echo '<p class=\"auspost-debug\" aupost ln454 $arrayquotes = <br> '; var_dump($arrayquotes) . ' </p>'; //  BMH DEBUG
            }   // BMH debug eof array quotes

            $i = 0 ;  // counter
            foreach($xmlquote_letter as $foo => $bar) {
                $code = ($xmlquote_letter->service[$i]->code);          //BMH keep API code for label
                $servicecode = $code;                                   // fully formatted API $code required for later sub quote
                $code = str_replace("_", " ", $code); $code = substr($code,11); // replace underscores with spaces

                $id = str_replace("_", "", $xmlquote_letter->service[$i]->code);
                        // remove underscores from AusPost methods. Zen Cart uses underscore as delimiter between module and method.
                        // underscores must also be removed from case statements below.

                $cost = (float)($xmlquote_letter->service[$i]->price);

                $description =  ($code) ;                           // BMH append name to code
                $descx = ucwords(strtolower($description));         // make sentence case
                $description = $letterprefix . $descx . $MSGLETTERTRACKING;     // BMH Prepend LETTER to CODE to differentiate from Parcels code + ADD letter tracking note

                if (( MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes"))  {
                    echo "<table class=\"aupost-debug\"><tr><td>" ; echo " ln474 LETTER ID= $id DESC= $description COST= $cost " ; echo "</td></tr></table>" ;
                }  // BMH Debug 2nd level debug each line of quote parsed /// 3rd

                $qqid = $id;
                $arrayquotes[$i]["qid"] = trim($qqid) ;             // BMH ** DEBUG echo '<br>ln 478 $arrayquotes[$i]["qid"]= ' . $arrayquotes[$i]["qid"] ;
                $arrayquotes[$i]["qcost"] = $cost;                  // BMH ** DEBUG echo '<br> ln 479 $arrayquotes[$i]["qcost"]= ' . $arrayquotes[$i]["qcost"];
                $arrayquotes[$i]["qdescription"] = $description;    // BMH ** DEBUG echo '<br> ln 480 $arrayquotes[$i]["qdescription"]= ' . $arrayquotes[$i]["qdescription"];

                $i++;   // increment the counter

                $add = 0 ; $f = 0 ; $info=0 ;

                switch ($id) {

                case  "AUSLETTEREXPRESSSMALL" ;
                case  "AUSLETTEREXPRESSMEDIUM" ;
                case  "AUSLETTEREXPRESSLARGE" ;
                    if ((in_array("Aust Express", $this->allowed_methods_l))) {
                        $add = MODULE_SHIPPING_AUPOST_LETTER_EXPRESS_HANDLING ; $f = 1 ;

                        if
                            (in_array("Aust Express Insured (no sig)" , $this->allowed_methods_l) ||
                            in_array("Aust Express Insured +sig" , $this->allowed_methods_l) ||
                            in_array("Aust Express +sig", $this->allowed_methods_l))   {       // check for any options for express letter

                            $optioncode_ec = 'AUS_SERVICE_OPTION_STANDARD';
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            $optioncode_sig = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                            $optioncode = $optioncode_sig;          // BMH DEBUG
                            if ($ordervalue < $MINVALUEEXTRACOVER){
                                $ordervalue = $MINVALUEEXTRACOVER;
                            }
                            //BMH DEBUG mask for testing // setting value forces extra cover on receipt at Post office
                                //$ordervalue = 101;
                                // BMH ** DEBUG to force extra cover value FOR TESTING ONLY; auto cover to $100

                            // ++++++ get special price for options available with Express letters +++++
                            $quL2 = $this->get_auspost_api(
                            'https://' . $aupost_url_string . LETTER_URL_STRING_CALC . "service_code=$servicecode&weight=$letterweight&option_code=$optioncode&suboption_code=$suboptioncode&extra_cover=$ordervalue") ;
                            $xmlquote_letter2 = ($quL2 == '') ? array() : new \SimpleXMLElement($quL2); // XML format

                            $i2 = 0 ;  // counter for new xmlquote

                            // BMH DEBUG bof XML formatted output
                            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                                echo "<p class=\"aupost-debug\" ><strong>>> Server Returned - LETTERS APDEBUG1+2 aupost line 494 << </strong><textarea rows=30 cols=100 style=\"margin:0;\"> ";
                                print_r($xmlquote_letter2) ; // exit ; // ORIG DEBUG to output api xml // BMH DEBUG
                                echo "</textarea></p";
                            }   // eof debug

                            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                                echo "<br><table class=\"aupost-debug\"><tr><td><b>auPost - Server Returned APDEBUG2 aupost ln525 LETTERS: output \$quL2</b><br>" . $quL2 . "</td></tr></table>" ;
                            }
                            // -- BMH DEBUG eof XML formatted output----

                            $id_exc_sig = "AUSLETTEREXPRESS" . "AUSSERVICEOPTIONSTANDARD";
                            $id_exc = "AUSLETTEREXPRESS" . "AUSSERVICEOPTIONEXTRACOVER";
                            $id_sig = "AUSLETTEREXPRESS" . "AUSSERVICEOPTIONSIGNATUREONDELIVERY";

                            $codeitem = ($xmlquote_letter2->costs->cost[0]->item);    // postage type description
                            $desc2 = $codeitem;
                            $desc_sig = $xmlquote_letter2->costs->cost[1]->item ;     // find the name for sig
                            $desc_excover = $xmlquote_letter2->costs->cost[2]->item ; // find the name for extra cover
                            $desc_excover_sig = $desc_sig . " + " . $xmlquote_letter2->costs->cost[2]->item ; // find the name for sig plus extra cover

                            $cost_excover= ((float)($xmlquote_letter2->costs->cost[0]->cost) + ($xmlquote_letter2->costs->cost[2]->cost)); // add basic postage cost + extra cover cost

                            $cost_sig = (float)($xmlquote_letter2->costs->cost[0]->cost) + ($xmlquote_letter2->costs->cost[1]->cost);       // basic cost + signature
                            $cost_excover_sig = (float)($xmlquote_letter2->total_cost); // total cost for all options

                            $cost_excover_sig = $cost_excover_sig/11 *10;       // remove tax
                            $cost_excover =  $cost_excover /11*10;              // remove tax
                            $cost_sig= $cost_sig /11*10;                        // remove tax

                            // got all of the values // -----------
                            $desc_excover = trim($desc2) . ' + ' . $desc_excover;
                            $desc_sig = trim($desc2) . ' + ' . $desc_sig;
                            $desc_excover_sig = trim($desc2) . ' + ' . $desc_excover_sig;

                            // ---------------
                            $arraytoappend_excover = array("qid"=>$id_exc, "qcost"=>$cost_excover, "qdescription"=>$desc_excover );
                            $arraytoappend_sig = array("qid"=>$id_sig, "qcost"=>$cost_sig, "qdescription"=>$desc_sig );
                            $arraytoappend_ex_sig = array("qid"=>$id_exc_sig, "qcost"=>$cost_excover_sig, "qdescription"=> $desc_excover_sig );

                            // append allowed express option types to main array
                            $arrayquotes[] = $arraytoappend_excover;
                            $arrayquotes[] = $arraytoappend_sig;
                            $arrayquotes[] = $arraytoappend_ex_sig;

                            // // ++++++
                            $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                            // //  ++++++++

                            // update returned methods for each option
                            if (in_array("Aust Express Insured +sig", $this->allowed_methods_l)) {
                                if (strlen($id) >1) {
                                    $methods[] = array("id"=>$id_exc_sig,  "title"=>$letterprefix . ' '. $desc_excover_sig . ' ' . $details, "cost"=>$cost_excover_sig) ;
                                 }
                            }

                            if (in_array("Aust Express Insured (no sig)", $this->allowed_methods_l)){
                                if (strlen($id) >1) {
                                    $methods[] = array('id' => $id_exc,  'title' => $letterprefix . ' '. $desc_excover . ' ' .$details, 'cost' => $cost_excover);
                                 }
                            }

                            if (in_array("Aust Express +sig", $this->allowed_methods_l)) {
                                if (strlen($id) >1) {
                                    $methods[] = array('id' => $id_sig,  'title' => $letterprefix . ' '. $desc_sig . ' ' .$details, 'cost' => $cost_sig);
                                 }
                            }
                            $description = $letterprefix . $descx; // set desc for express without the no tracking msg

                        }   // eof // Express plus options

                    }
                break;  //eof express

                case  "AUSLETTERPRIORITYSMALL" ;    // normal own packaging + label
                case  "AUSLETTERPRIORITYLARGE125" ; // normal own packaging + label
                case  "AUSLETTERPRIORITYLARGE250" ; // normal own packaging + label
                case  "AUSLETTERPRIORITYLARGE500" ; // normal own packaging + label
                    if ((in_array("Aust Priority", $this->allowed_methods_l)))
                    {
                        $add =  MODULE_SHIPPING_AUPOST_LETTER_PRIORITY_HANDLING ; $f = 1 ;
                    }
                    break;

                case  "AUSLETTERREGULARSMALL";      // normal mail - own packaging
                case  "AUSLETTERREGULARLARGE125";   // normal mail - own packaging
                case  "AUSLETTERREGULARLARGE250";   // normal mail - own packaging
                case  "AUSLETTERREGULARLARGE500";   // normal mail - own packaging
                    if (in_array("Aust Standard", $this->allowed_methods_l))
                    {
                        $add = MODULE_SHIPPING_AUPOST_LETTER_HANDLING ; $f = 1 ;
                    }
                    break;

                case  "AUSLETTERSIZEDL";  // This requires purchase of Aus Post packaging   // BMH Not processed
                case  "AUSLETTERSIZEC6";  // This requires purchase of Aus Post packaging   // BMH Not processed
                case  "AUSLETTERSIZEC5";  // This requires purchase of Aus Post packaging   // BMH Not processed
                case  "AUSLETTERSIZEC5";  // This requires purchase of Aus Post packaging   // BMH Not processed
                case  "AUSLETTERSIZEC4";  // This requires purchase of Aus Post packaging   // BMH Not processed
                case  "AUSLETTERSIZEB4";  // This requires purchase of Aus Post packaging   // BMH Not processed
                case  "AUSLETTERSIZEOTH"; // This requires purchase of Aus Post packaging   // BMH Not processed
                //case  "AUSLETTEREXPRESSDL"  // Same as AUSLETTEREXPRESSSMALL      // not returend by AusPost 2023-09
                //case  "AUSLETTEREXPRESSC5"  // Same as AUSLETTEREXPRESSMEDIUM     // not returend by AusPost 2023-09
                //case  "AUSLETTEREXPRESSB4"  // Same as AUSLETTEREXPRESSLARGE      // not returend by AusPost 2023-09
                    $cost = 0;$f=0;
                    // echo "shouldn't be here"; //BMH debug
                    //do nothing - ignore the code
                    break;

                }  // end switch

                // bof only list valid options without debug info // BMH
                 if ((($cost > 0) && ($f == 1)) ) { //
                    $cost = $cost + floatval($add) ;     // add handling fee   // string to float

                    // GST (tax) included in all prices in Aust
                    if (($dest_country == "AU") && (($this->tax_class) > 0)) {
                        $t = $cost - ($cost / (Tax::get_tax_rate($this->tax_class, $this->delivery['country']['id'], $this->delivery['zone_id']))) ;
                        if ($t > 0) $cost = $t ;
                    }

                    $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                     // //  ++++++++

                    // UPDATE THE RECORD FOR DISPLAY
                    $cost = $cost / $aus_rate;
                    // METHODS ADD to returned quote for letter
                     if (strlen($id) >1) {
                        $methods[] = array('id' => "$id",  'title' => $description .  $details, 'cost' => ($cost ));
                     }
                }  // end display output //////// only list valid options without debug info // BMH

            }  // eof foreach loop

            //  check to ensure we have at least one valid LETTER quote - produce error message if not.
            if  (sizeof($methods) == 0) {
                $cost = $this->_get_error_cost($dest_country) ; // retrieve default rate

               if ($cost == 0)  return  ;

               $methods[] = array( 'id' => "Error",  'title' =>MODULE_SHIPPING_AUPOST_TEXT_ERROR ,'cost' => $cost ) ;
            }
        }
        //// EOF LETTERS /////////

        //////////// // PACKAGE ADJUSTMENT FOR OPTIMAL PACKING // ////////////
        // package created, now re-orientate and check dimensions
        $parcelheight = ceil($parcelheight);  // round up to next integer // cm for accuracy in pricing
        $var = array($parcelheight, $parcellength, $parcelwidth) ; sort($var) ;
        $parcelheight = $var[0] ; $parcelwidth = $var[1] ; $parcellength = $var[2] ;
        $girth = ($parcelheight * 2) + ($parcelwidth * 2)  ;

        $parcelweight = $parcelweight + (($parcelweight*$tare)/100) ;

        if (MODULE_SHIPPING_AUPOST_WEIGHT_FORMAT == "gms") {$parcelweight = $parcelweight/1000 ; }

        //  save dimensions for display purposes on quote form
        $_SESSION['swidth'] = $parcelwidth ; $_SESSION['sheight'] = $parcelheight ;
        $_SESSION['slength'] = $parcellength ;  // $_SESSION['boxes'] = $shipping_num_boxes ;

        // Check for maximum length allowed
        if($parcellength > $MAXLENGTH_P) {
             $cost = $this->_get_error_cost($dest_country) ;

           if ($cost == 0) return  ;    // no quote
            $methods[] = array('title' => ' (AusPost excess length)', 'cost' => $cost ) ; // update method
            $this->quotes['methods'] = $methods;   // set it
            return $this->quotes;
        }  // exceeds AustPost maximum length. No point in continuing.

       // Check girth - no longer used
       /*
        if($girth > $MAXGIRTH_P ) {
             $cost = $this->_get_error_cost($dest_country) ;
           if ($cost == 0)  return  ;   // no quote
            $methods[] = array('title' => ' (AusPost excess girth)', 'cost' => $cost ) ;
            $this->quotes['methods'] = $methods;   // set it
            return $this->quotes;
        }  // exceeds AustPost maximum girth. No point in continuing.
        */
        // Girth no longer used

        // Check cubic volume
        if($item_cube > $MAXCUBIC_P ) {
             $cost = $this->_get_error_cost($dest_country) ;
           if ($cost == 0)  return  ;   // no quote
            $methods[] = array('title' => ' (AusPost excess cubic vol / girth)', 'cost' => $cost ) ;
            $this->quotes['methods'] = $methods;   // set it
            return $this->quotes;
        }  // exceeds AustPost maximum cubic volume. No point in continuing.

        if ($parcelweight > $MAXWEIGHT_P) {
            $cost = $this->_get_error_cost($dest_country) ;
            if ($cost == 0)  return ;   // no quote

            $methods[] = array('title' => ' (AusPost excess weight)', 'cost' => $cost ) ;
            $this->quotes['methods'] = $methods;   // set it
            return $this->quotes;
        }  // exceeds AustPost maximum weight. No point in continuing.

        // Check to see if cache is useful
        if (USE_CACHE == "Yes") {   //BMH DEBUG disable cache for testing
            if(isset($_SESSION['aupostParcel']))
            {
                $test = explode(",", $_SESSION['aupostParcel']) ;

                if (
                    ($test[0] == $dest_country) &&
                    ($test[1] == $topcode) &&
                    ($test[2] == $parcelwidth) &&
                    ($test[3] == $parcelheight) &&
                    ($test[4] == $parcellength) &&
                    ($test[5] == $parcelweight) &&
                    ($test[6] == $ordervalue)
                   )
                {
                    if (MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) {
                        echo "<center><table border=1 width=95% ><td align=center><font color=\"#FF0000\">Using Cached quotes </font></td></table></center>" ;
                    }

                    $this->quotes =  isset($_SESSION['aupostQuotes']) ;  //BMH
                    return $this->quotes ;
                    ///////////////////////////////////  Cache Exit Point //////////////////////////////////
                } // No cache match -  get new quote from server //
            }  // No cache session -  get new quote from server //
        } // end cache option //BMH DEBUG
        ///////////////////////////////////////////////////////////////////////////////////////////////

        // always save new session  CSV //
        $_SESSION['aupostParcel'] = implode(",", array($dest_country, $topcode, $parcelwidth, $parcelheight, $parcellength, $parcelweight, $ordervalue)) ;
        $shipping_weight = $parcelweight ;  // global value for zencart

        $dcode = ($dest_country == "AU") ? $topcode:$dest_country ; // Set destination code ( postcode if AU, else 2 char iso country code )

        if (!$dcode) $dcode =  SHIPPING_ORIGIN_ZIP ; // if no destination postcode - eg first run, set to local postcode

        $flags = ((MODULE_SHIPPING_AUPOST_HIDE_PARCEL == "No") || ( MODULE_SHIPPING_AUPOST_DEBUG == "Yes" )) ? 0:1 ;

        $aupost_url_string = AUPOST_URL_PROD ;  // Server query string //
        // if test mode replace with test variables - url + api key
        if (AUPOST_MODE == 'Test') {
            //$aupost_url_string = AUPOST_URL_TEST ; Aus Post say to use production servers (2022)
            $aupost_url_apiKey = AUPOST_TESTMODE_AUTHKEY;
        }
        if (MODULE_SHIPPING_AUPOST_DEBUG == "Yes") {
            echo "<center> <table class=\"aupost-debug-table\" border=1>
            <tr> <th colspan=2> Pasrcel dims sent </th> <td colspan=6> Length sent=$parcellength; Width sent=$parcelwidth; Height sent=$parcelheight; </td> </tr>  </table></center> ";
        }
        if (MODULE_SHIPPING_AUPOST_DEBUG == "Yes" && APDEBUG1 == "Yes") {
            echo '<p class="aupost-debug"> <br>parcels ***<br>aupost ln767 ' .'https://' . $aupost_url_string . PARCEL_URL_STRING . MODULE_SHIPPING_AUPOST_SPCODE . "&to_postcode=$dcode&length=$parcellength&width=$parcelwidth&height=$parcelheight&weight=$parcelweight" . '</p>';
        }
        //// ++++++++++++++++++++++++++++++
        // get parcel api';
        $qu = $this->get_auspost_api(
          'https://' . $aupost_url_string . PARCEL_URL_STRING . MODULE_SHIPPING_AUPOST_SPCODE . "&to_postcode=$dcode&length=$parcellength&width=$parcelwidth&height=$parcelheight&weight=$parcelweight") ;
        // // +++++++++++++++++++++++++++++

        if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG2 == "Yes")) {
            echo "<table class='aupost-debug'><tr><td><b>auPost - Server Returned APDEBUG2 ln776:</b><br>" . $qu . "</td></tr></table> " ;
        }  //eof debug

        // Check for returned quote is really an error message
        //
            if(str_starts_with($qu, "{" )) {
            $myerrorarray=json_decode($qu); echo '<br> ln782 ';  //BMH
            print_r($myerrorarray);
            echo '<br> ln784 myerrorarray[status] = ' . $myerrorarray[status];
            $myerrorarray=json_decode($qu);
            echo '<br> ln786 $myerrorarray ='; print_r($myerrorarray);
                if ($myerrorarray[status] = "Failed") {
                //echo '<br> ln788 $myerrorarray[status] ' . $myerrorarray['status'] . ' ';
                // echo '<br> Australia Post connection FAILED. Please report error ' .
                echo '<br> Australia Post connection ' . $myerrorarray['status'] . '. Please report error ';
                    print_r($myerrorarray); echo ' to site owner';
                return $this->quotes;
                }
        }
        //BMH
        $xml = ($qu == '') ? array() : new \SimpleXMLElement($qu) ; // If we have any results, parse them into an array

        if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
            echo "<p class='aupost-debug1' ><strong> >> Server Returned APDEBUG1+2 line 780 << <br> </strong> <textarea  > ";
            print_r($xml) ; // output api xml // BMH DEBUG
            echo "</textarea> </p>";
        }
        /////  Initialise our quotes['id'] required in includes/classes/shipping.php
        $this->quotes = array('id' => $this->code, 'module' => $this->title);

        ///////////////////////////////////////
        //  loop through the Parcel quotes retrieved //
        $i = 0 ;  // counter
        if (( MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes"))  {
            echo '<br> ln 810 $this->allowed_methods = '; var_dump($this->allowed_methods); // BMH ** DEBUG
        }

        foreach($xml as $foo => $bar) {

            $code = ($xml->service[$i]->code); $code = str_replace("_", " ", $code); $code = substr($code,11); //strip first 11 chars;     //BMH keep API code for label

            $id = str_replace("_", "", $xml->service[$i]->code);    // remove underscores from AusPost methods. Zen Cart uses underscore as delimiter between module and method. // underscores must also be removed from case statements below.
            $cost = (float)($xml->service[$i]->price);

             $description =  "PARCEL " . (ucwords(strtolower($code))) ; // BMH prepend PARCEL to code in sentence case

            if (( MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes"))  {
                echo "<table class='aupost-debug'><tr><td>" ;
                echo "ln 824 ID= $id  DESC= $description COST= $cost inc" ;
                echo "</td></tr></table>" ;
              } // BMH 2nd level debug each line of quote parsed

              $add = 0 ; $f = 0 ; $info=0 ;

            switch ($id) {

                case  "AUSPARCELREGULARSATCHELEXTRALARGE" ; // fall through and treat as one block
                case  "AUSPARCELREGULARSATCHELLARGE" ;      // fall through and treat as one block
                case  "AUSPARCELREGULARSATCHELMEDIUM" ;     // fall through and treat as one block
                case  "AUSPARCELREGULARSATCHELSMALL" ;      // fall through and treat as one block
                //case  "AUSPARCELREGULARSATCHEL500G" ;       // fall through and treat as one block

                    if (in_array("Prepaid Satchel", $this->allowed_methods,$strict = true)) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln840 allowed option = prepaid satchel';
                        }
                        $optioncode =""; $optionservicecode = ""; $suboptioncode = ""; $allowed_option ="";
                        $add = MODULE_SHIPPING_AUPOST_PPS_HANDLING ;
                        $f = 1 ;

                        if ((($cost > 0) && ($f == 1))) { //
                            $cost = $cost + floatval($add) ;        // string to float
                            if ( MODULE_SHIPPING_AUPOST_CORE_WEIGHT == "Yes")  $cost = ($cost * $shipping_num_boxes) ;

                        // CALC TAX and remove from returned amt as tax is added back in on checkout
                        if (($dest_country == "AU") && (($this->tax_class) > 0)) {
                            $t = $cost - ($cost / (zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id'])+1)) ;
                            if ($t > 0) $cost = $t ;
                        }
                         $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                         }   // eof list option for normal operation
                         $cost = $cost / $aus_rate;

                        $methods[] = array('id' => "$id",  'title' => $description . " " . $details, 'cost' => $cost);   // update method
                    }

                    if ( in_array("Prepaid Satchel Insured +sig", $this->allowed_methods) ) {
                       if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            //$id_option = $id;
                            //$id_option = $id . $optioncode . $suboptioncode;
                            $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "", $suboptioncode);

                            $allowed_option = "Prepaid Satchel Insured +sig";
                            $option_offset = 0;

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if (strlen($id) >1) {
                                $methods[] = $result_secondary_options ;
                            }
                       }
                    }

                    if ( in_array("Prepaid Satchel +sig", $this->allowed_methods) ) {

                        $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                        $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                        $suboptioncode = '';
                        //$id_option = $id . "AUSSERVICEOPTIONSIGNATUREONDELIVERY";;
                        //$id_option = $id . $optioncode . $suboptioncode;
                        $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);
                        $allowed_option = "Prepaid Satchel +sig";

                        $option_offset = 0;

                        $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                        if (strlen($id) >1){
                            $methods[] = $result_secondary_options ;
                        }
                    }

                    if ( in_array("Prepaid Satchel Insured (no sig)", $this->allowed_methods) ) {
                        if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_STANDARD';
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            //$id_option = $id;
                            //$id_option = $id . $optioncode . $suboptioncode;
                            $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);
                            $allowed_option = "Prepaid Satchel Insured (no sig)";
                            $option_offset1 = 0;

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                                echo '<p class="aupost-debug"> ln915 $result_secondary_options = ' ; //BMH ** DEBUG
                                var_dump($result_secondary_options);
                                echo ' <\p>';
                            }

                            if (strlen($id) >1){
                                $methods[] = $result_secondary_options ;
                            }
                        }
                    }

                    break;

                case  "AUSPARCELEXPRESSSATCHELEXTRALARGE" ; // fall through and treat as one block
                case  "AUSPARCELEXPRESSSATCHELLARGE" ;      // fall through and treat as one block
                case  "AUSPARCELEXPRESSSATCHELMEDIUM" ;     // fall through and treat as one block
                case  "AUSPARCELEXPRESSSATCHELSMALL" ;      // fall through and treat as one block

                    if ((in_array("Prepaid Express Satchel", $this->allowed_methods))) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln935 allowed option = parcel express satchel';
                        }
                        $optioncode =""; $optionservicecode = ""; $suboptioncode = "";
                        $add =  MODULE_SHIPPING_AUPOST_PPSE_HANDLING ;
                        $f = 1 ;

                        if ((($cost > 0) && ($f == 1))) { //
                            $cost = $cost + floatval($add) ;        // string to float
                            if ( MODULE_SHIPPING_AUPOST_CORE_WEIGHT == "Yes")  $cost = ($cost * $shipping_num_boxes) ;

                        // CALC TAX and remove from returned amt as tax is added back in on checkout
                        if (($dest_country == "AU") && (($this->tax_class) > 0)) {
                            $t = $cost - ($cost / (Tax::get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id'])+1)) ;
                            if ($t > 0) $cost = $t ;
                        }
                         $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                         }   // eof list option for normal operation
                         $cost = $cost / $aus_rate;

                        $methods[] = array('id' => "$id",  'title' => $description . " " . $details, 'cost' => $cost);   // update method
                    }
                    if ( in_array("Prepaid Express Satchel Insured +sig", $this->allowed_methods) ) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln958 allowed option = parcel express satchel ins+sig'; }
                       if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                           // $id_option = $id;
                           //$id_option = $id . $optioncode . $suboptioncode;
                           $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);
                            $allowed_option = "Prepaid Express Satchel Insured +sig";
                            $option_offset = 0;

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details,$dest_country, $order, $currencies, $aus_rate);

                            if (strlen($id) >1) {
                                $methods[] = $result_secondary_options ;
                            }
                       }
                    }

                    if ( in_array("Prepaid Express Satchel +sig", $this->allowed_methods) ) {
                        $allowed_option = "Prepaid Express Satchel +sig";
                        $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                        $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                        $suboptioncode = '';
                        //$id_option = $id . "AUSSERVICEOPTIONSIGNATUREONDELIVERY";
                        //$id_option = $id . $optioncode . $suboptioncode;
                        $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);

                        $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details,$dest_country, $order, $currencies, $aus_rate);

                        if (strlen($id) >1) {
                            $methods[] = $result_secondary_options ;
                        }
                    }

                    if ( in_array("Prepaid Express Satchel Insured (no sig)", $this->allowed_methods) ) {
                        if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $allowed_option = "Prepaid Express Satchel Insured (no sig)";
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $optioncode = 'AUS_SERVICE_OPTION_STANDARD';
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            //$id_option = $id . $optioncode . $suboptioncode;
                            $id_option = $id . str_replace("_", "", $optioncode) . str_replace("_", "", $suboptioncode);

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if (strlen($id) >1) {
                                $methods[] = $result_secondary_options ;
                            }
                        }
                    }
                    break;

                case  "AUSPARCELREGULARPACKAGESMALL";       // fall through and treat as one block
                case  "AUSPARCELREGULARPACKAGE";            // normal mail - own packaging
                case  "AUSPARCELREGULAR";                   // normal mail - own packaging
                    if (in_array("Regular Parcel", $this->allowed_methods,$strict = true)) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln1016 allowed option = parcel regular';
                        }
                        $optioncode =""; $optionservicecode = ""; $suboptioncode = ""; $allowed_option ="";
                        $add = MODULE_SHIPPING_AUPOST_RPP_HANDLING ;
                        $f = 1 ;
                        $apr = 1;

                        if ((($cost > 0) && ($f == 1))) { //
                            $cost = $cost + floatval($add) ;        // string to float
                            if ( MODULE_SHIPPING_AUPOST_CORE_WEIGHT == "Yes")  $cost = ($cost * $shipping_num_boxes) ;

                        // CALC TAX and remove from returned amt as tax is added back in on checkout
                        if (($dest_country == "AU") && (($this->tax_class) > 0)) {
                            $t = $cost - ($cost / (zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id'])+1)) ;
                            if ($t > 0) $cost = $t ;
                        }
                         $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                         }   // eof list option for normal operation
                         $cost = $cost / $aus_rate;

                         $methods[] = array('id' => "$id",  'title' => $description . " " . $details, 'cost' => $cost);   // update method
                    }

                    if ( in_array("Regular Parcel Insured +sig", $this->allowed_methods) ) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln1041 allowed option = parcel regular ins + sig';
                        }
                        if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            //$suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            $id_option = $id . $optioncode . $suboptioncode;
                            $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);
                            $allowed_option = "Regular Parcel Insured +sig";
                            $option_offset = 0;

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if (strlen($id) >1) {
                                $methods[] = $result_secondary_options ;
                            }
                        }
                    }

                    if ( in_array("Regular Parcel +sig", $this->allowed_methods) ) {
                       if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln1062 allowed option = parcel regular + sig';
                        }
                        $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                        $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                   // BMH DEBUG      echo 'ln1066 debug $optionservicecode = ' . $optionservicecode ; //BMHDEBUG
                        $suboptioncode = '';
                        $id_option = $id . $optioncode . $suboptioncode;
                        //$id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);
                        $allowed_option = "Regular Parcel +sig";
                        $option_offset = 0;

                        $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                        if (strlen($id) >1){
                            $methods[] = $result_secondary_options ;
                        }
                    }

                    if ( in_array("Regular Parcel Insured (no sig)", $this->allowed_methods) ) {
                        if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_STANDARD';
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            //$id_option = $id . $optioncode . $suboptioncode;
                            $id_option = $id . str_replace("_", "",$optioncode) . str_replace("_", "",$suboptioncode);
                            $allowed_option = "Regular Parcel Insured (no sig)";
                            $option_offset1 = 0;

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                                echo '<p class="aupost-debug"> ln1093 $result_secondary_options = ' ; //BMH ** DEBUG
                                var_dump($result_secondary_options);
                                echo ' <\p>';
                            }
                            if (strlen($id) >1){
                                $methods[] = $result_secondary_options ;
                            }
                        }
                    }

                break;

                case  "AUSPARCELEXPRESS" ;              // express mail - own packaging
                    if (in_array("Express Parcel", $this->allowed_methods,$strict = true)) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln1108 allowed option = parcel express';
                        }
                        $optioncode =""; $optionservicecode = ""; $suboptioncode = ""; $allowed_option ="";
                        $add = MODULE_SHIPPING_AUPOST_EXP_HANDLING ;
                       // echo ' ln1121 MODULE_SHIPPING_AUPOST_EXP_HANDLING= '. MODULE_SHIPPING_AUPOST_EXP_HANDLING; //BMH DEBUG
                        $f = 1 ;
                        $apr = 1;

                        //$cost = (float)($xmlquote_2->total_cost);

                        // got all of the values // -----------


                        if ((($cost > 0) && ($f == 1))) { //
                            $cost = $cost + floatval($add) ;        // string to float
                            if ( MODULE_SHIPPING_AUPOST_CORE_WEIGHT == "Yes")  $cost = ($cost * $shipping_num_boxes) ;

                        // CALC TAX and remove from returned amt as tax is added back in on checkout
                            if (($dest_country == "AU") && (($this->tax_class) > 0)) {
                                $t = $cost - ($cost / (Tax::get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id'])+1)) ;
                                if ($t > 0) $cost = $t ;
                                }
                            // //  ++++
                            $info = 0;  // BMH Dummy used for REG POST - MAY BE REDUNDANT

                            $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                            // //  ++++

                        }   // eof list option for normal operation
                        $cost = $cost / $aus_rate;
                        $methods[] = array('id' => "$id",  'title' => $description . " " . $details, 'cost' => $cost);   // update method
                    }

                    if ( in_array("Express Parcel Insured +sig", $this->allowed_methods, $strict = true) ) {
                        if ((APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                            echo '<br> ln1143 allowed option = parcel express ins + sig';
                        }
                        if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                            $add = MODULE_SHIPPING_AUPOST_EXP_HANDLING ;
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            //$id_option = "AUSPARCELEXPRESS" . "AUSSERVICEOPTIONSIGNATUREONDELIVERYEXTRACOVER";
                            $id_option = $id . str_replace("_", "", $optioncode) . str_replace("_", "", $suboptioncode);
                            $allowed_option = "Express Parcel Insured +sig";
                            $option_offset = 0;

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if (strlen($id) >1){
                                $methods[] = $result_secondary_options ;
                            }
                        }
                    }

                    if ( in_array("Express Parcel +sig", $this->allowed_methods, $strict = true) ) {

                        $optioncode = 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY';
                        $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                        $suboptioncode = '';
                        $id_option = "AUSPARCELEXPRESS" . "AUSSERVICEOPTIONSIGNATUREONDELIVERY";
                        //$id_option = $id . str_replace("_", "",$optioncode);
                        $allowed_option = "Express Parcel +sig";

                        $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                        if (strlen($id) >1){
                            $methods[] = $result_secondary_options ;
                        }

                    }

                    if ( in_array("Express Parcel Insured (no sig)", $this->allowed_methods) )
                    {
                        if ($ordervalue > $MINVALUEEXTRACOVER) {
                            $optioncode = 'AUS_SERVICE_OPTION_STANDARD';
                            $optionservicecode = ($xml->service[$i]->code);  // get api code for this option
                            $suboptioncode = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                            //$id_option = "AUSPARCELEXPRESS" . "AUSSERVICEOPTIONEXTRACOVER";
                            $id_option = $id .str_replace("_", "",$suboptioncode);
                            $allowed_option = "Express Parcel Insured (no sig)";

                            $result_secondary_options = $this-> _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate);

                            if (strlen($id) >1){
                                $methods[] = $result_secondary_options ;
                            }
                        }
                    }

                break;

                case  "AUSPARCELEXPRESSSATCHEL5KG" ;        // superceded
                case  "AUSPARCELEXPRESSSATCHEL3KG" ;        // superceded
                case  "AUSPARCELEXPRESSSATCHEL1KG" ;        // superceded
                case  "AUSPARCELEXPRESSSATCHEL500G";        // superceded by AUSPARCELEXPRESSSATCHELSMALL
                //
                case  "AUSPARCELREGULARSATCHEL5KG" ;        // superceded by
                case  "AUSPARCELREGULARSATCHEL3KG" ;        // superceded by AUSPARCELREGULARSATCHELLARGE
                case  "AUSPARCELREGULARSATCHEL1KG" ;        // superceded
                case  "AUSPARCELREGULARSATCHEL500G";        // still returned but superceded by AUSPARCELREGULARSATCHELSMALL
                //
                case  "AUSPARCELEXPRESSPACKAGESMALL";       // This is cheaper but requires extra purchase of Aus Post packaging
                //
                //case  "AUSPARCELREGULARPACKAGESMALL";     // This is cheaper but requires extra purchase of Aus Post packaging
                case  "AUSPARCELREGULARPACKAGEMEDIUM";      // This is cheaper but requires extra purchase of Aus Post packaging
                case  "AUSPARCELREGULARPACKAGELARGE";       // This is cheaper but requires extra purchase of Aus Post packaging
                      // $optioncode =""; $optionservicecode = ""; $suboptioncode = "";
                    $cost = 0;$f=0; $add= 0;
                    // echo "shouldn't be here"; //BMH debug
                    //do nothing - ignore the code
                break;

                if (( MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes"))  {
                    echo "<table><tr><td>" ;  echo "ln1222 ID= $id  DESC= $description COST= $cost" ; echo "</td></tr></table>" ;
                } // BMH 2nd level debug each line of quote parsed
            }  // eof switch

            ////    only list valid options without debug info // BMH
            if ((($cost > 0) && ($f == 1))) { //&& ( MODULE_SHIPPING_AUPOST_DEBUG == "No" )) { //BMH DEBUG = ONLY if not debug mode
                $cost = $cost + floatval($add) ;        // string to float
                if ( MODULE_SHIPPING_AUPOST_CORE_WEIGHT == "Yes")  $cost = ($cost * $shipping_num_boxes) ;

                $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
            }   // eof list option for normal operation

            $cost = $cost / $aus_rate;

                if (( MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes"))  {
                    echo '<p class="aupost-debug"> ln1237 $i=' .$i . "</p>";
                } // BMH 3rd level debug each line of quote parsed

            $i++; // increment the counter to match XML array index
        }  // end foreach loop

        //  //  ///////////////////////////////////////////////////////////////////
        //
        //  check to ensure we have at least one valid quote - produce error message if not.
        if  (sizeof($methods) == 0) {                       // no valid methods
            $cost = $this->_get_error_cost($dest_country) ; // give default cost
            if ($cost == 0)  return  ;                      //

           $methods[] = array( 'id' => "Error",  'title' =>MODULE_SHIPPING_AUPOST_TEXT_ERROR ,'cost' => $cost ) ; // display reason
        }

        // // // sort array by cost       // // //
        $sarray[] = array() ;
        $resultarr = array() ;

        foreach($methods as $key => $value) {
            $sarray[ $key ] = $value['cost'] ;
        }
        asort( $sarray ) ;

        foreach($sarray as $key => $value)

        //  remove zero values from postage options
        foreach ($sarray as $key => $value) {
            if ($value == 0 ) {
            }
            else
            {
            $resultarr[ $key ] = $methods [ $key ] ;
            }
        } // BMH eof remove zero values

        $resultarrunique = array_unique($resultarr,SORT_REGULAR);   // remove duplicates

        $this->quotes['methods'] = array_values($resultarrunique) ;   // set it

// BMH DEBUG
        if ($this->tax_class >  0) {
          $this->quotes['tax'] = Tax::get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
        }

        if (APDEBUG2 == "Yes") {
            echo '<p class="aupost-debug"> <br>parcels ***<br>aupost ln1284 ' .'https://' . $aupost_url_string . PARCEL_URL_STRING .
                MODULE_SHIPPING_AUPOST_SPCODE . "&to_postcode=$dcode&length=$parcellength&width=$parcelwidth&height=$parcelheight&weight=$parcelweight" . '</p>';
        }
        if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title, 80,60, 'style="padding: 0px 0px 0px 20px;"');
        $_SESSION['aupostQuotes'] = $this->quotes  ; // save as session to avoid reprocessing when single method required

        return $this->quotes;   //  all done //

        //  //  ///////////////////////////////  Final Exit Point //////////////////////////////////
    } // eof function quote method

    


function _get_secondary_options( $add, $allowed_option, $ordervalue, $MINVALUEEXTRACOVER, $dcode, $parcellength, $parcelwidth, $parcelheight, $parcelweight, $optionservicecode, $optioncode, $suboptioncode, $id_option, $description, $details, $dest_country, $order, $currencies, $aus_rate)
    {
        $aupost_url_string = AUPOST_URL_PROD ;  // Server query string //

        if ((in_array($allowed_option, $this->allowed_methods))) {
            //$add = MODULE_SHIPPING_AUPOST_RPP_HANDLING ;
            $f = 1 ;

                // DEBUGGING CODE to force extracover calculation // BMH
                    //if ($ordervalue < $MINVALUEEXTRACOVER){
                    //    $ordervalue = $MINVALUEEXTRACOVER;
                    //} //BMH DEBUG mask for testing

            $ordervalue = ceil($ordervalue);  // round up to next integer

            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                echo '<p class="aupost-debug"> <br> ln1314 allowed option = ' . $allowed_option . '<\p>';
                echo '<p class="aupost-debug"> <br> ln1315 ' . PARCEL_URL_STRING_CALC . MODULE_SHIPPING_AUPOST_SPCODE .
                    "&to_postcode=$dcode&length=$parcellength&width=$parcelwidth&height=$parcelheight&weight=$parcelweight
                    &service_code=$optionservicecode&option_code=$optioncode&suboption_code=$suboptioncode&extra_cover=
                    $ordervalue" . "<\p>"; // BMH ** DEBUG
            }

            $qu2 = $this->get_auspost_api( 'https://' . $aupost_url_string . PARCEL_URL_STRING_CALC. MODULE_SHIPPING_AUPOST_SPCODE . "&to_postcode=$dcode&length=$parcellength&width=$parcelwidth&height=$parcelheight&weight=$parcelweight&service_code=$optionservicecode&option_code=$optioncode&suboption_code=$suboptioncode&extra_cover=$ordervalue") ;

            $xmlquote_2 = ($qu2 == '') ? array() : new \SimpleXMLElement($qu2); // XML format

            if ((MODULE_SHIPPING_AUPOST_DEBUG == "Yes" ) && (APDEBUG1 == "Yes") && (APDEBUG2 == "Yes")) {
                echo '<p class="aupost-debug"> <br> ln1326 $allowed_option = ' . $allowed_option . '<\p>';
                    echo "<p class=\"aupost-debug\"><strong>>> Server Returned APDEBUG1+2 ln1328 options<< </strong> <br> <textarea> ";
                    print_r($xmlquote_2) ; // exit ; // // BMH DEBUG
                    echo "</textarea>";
            }

            $invalid_option = $xmlquote_2->errorMessage;

            if (empty($invalid_option)) {
            // -- BMH DEBUG eof XML formatted output----

            $desc_option = $allowed_option;
            $cost_option = (float)($xmlquote_2->total_cost);

            // got all of the values // -----------
            $cost = $cost_option;

            if ((($cost > 0) && ($f == 1))) { //
                $cost = $cost + floatval($add) ;        // string to float
                if ( MODULE_SHIPPING_AUPOST_CORE_WEIGHT == "Yes")  $cost = ($cost * $shipping_num_boxes) ;

            // CALC TAX and remove from returned amt as tax is added back in on checkout
              if (($dest_country == "AU") && (($this->tax_class) > 0)) {
                   $t = $cost - ($cost / (Tax::get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id'])+1)) ;
                   if ($t > 0) $cost = $t ;
                 }
               // //  ++++
                $info = 0;  // BMH Dummy used for REG POST - MAY BE REDUNDANT

                $details= $this->_handling($details,$currencies,$add,$aus_rate,$info);  // check if handling rates included
                // //  ++++

            }   // eof list option for normal operation
            $cost = $cost / $aus_rate;

            $desc_option = "[" . $desc_option . "]";         // delimit option in square brackets
            $result_secondary_options = array("id"=>$id_option,  "title"=>$description . ' ' . $desc_option . ' ' .$details, "cost"=>$cost) ;
        } // valid result
         else {      // pass back a zero value as not a valid option from Australia Post eg extra cover may require a signature as well
            $cost = 0;
            $result_secondary_options = array("id"=> '',  "title"=>'', "cost"=>$cost) ;  // invalid result
        }
        }   // eof // Express plus options

    return $result_secondary_options;
    } // eof function _get_secondary_options //
// // // BMH _get_secondary_options

    // // //
    function _get_error_cost($dest_country)
    {
        $x = explode(',', MODULE_SHIPPING_AUPOST_COST_ON_ERROR) ;
        unset($_SESSION['aupostParcel']) ;  // don't cache errors.
        $cost = $dest_country == "AU" ?  $x[0]:$x[1] ;
        if ($cost == 0) {
            $this->enabled = FALSE ;
            unset($_SESSION['aupostQuotes']) ;
        }
        else
        {
        $this->quotes = array('id' => $this->code, 'module' => 'Flat Rate');
        }
        return $cost;
    }

    //  //  ////////////////////////////////////////////////////////////
    // BMH - parts for admin module
    /*function check($platform_id)
    {
        global $db, $platform_id;
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_AUPOST_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    } */
	public function check( $platform_id ) {
		$keys = $this->keys();
		if ( count($keys) == 0 || ((int)$platform_id == 0 && !$this->isExtension)) return 0;
		$check_keys_r = tep_db_query(
		  "SELECT configuration_key ".
		  "FROM " . TABLE_PLATFORMS_CONFIGURATION . " ".
		  "WHERE configuration_key IN('".implode("', '",array_map('tep_db_input',$keys))."') AND platform_id='".(int)$platform_id."'"
		);
		$installed_keys = array();
		while( $check_key = tep_db_fetch_array($check_keys_r) ) {
		  $installed_keys[$check_key['configuration_key']] = $check_key['configuration_key'];
		}

		$check_status = isset($installed_keys[$keys[0]])?1:0;

		$install_keys = false;
		foreach( $keys as $idx=>$module_key ) {
		  if ( !isset($installed_keys[$module_key]) && $check_status ) {
			// missing key
			if ( !is_array($install_keys) ) $install_keys = $this->get_install_keys($platform_id);
			$this->add_config_key($platform_id, $module_key, $install_keys[$module_key]);
		  }
		}

		return $check_status;
  }

    //  //  ////////////////////////////////////////////////////////////////////////
    function install($platform_id) {
        global $db;
        // check for XML // BMH
        if (!class_exists('SimpleXMLElement')) {
			$messageStack->add_session(
			'Installation FAILED. AusPpost requires SimpleXMLElement to be installed on the system '
		);
		echo "This module requires SimpleXMLElement to work. Most Web hosts will support this.<br>Installation will NOT continue.<br>Press your back-page to continue ";
        exit;
		}
		$keys = $this->keys();
		if ( count($keys) == 0 || ((int)$platform_id == 0 && !$this->isExtension)) return 0;
		$result_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'SHIPPING_ORIGIN_ZIP'");
        //$result = tep_db_fetch_array($result_query);
		while($row = tep_db_fetch_array($result_query)) {
			if ($row['configuration_value']){
				$pcode = $row['configuration_value'] ;
			} else {
				$pcode = "2000" ;
			}
		}		
        /////////////////////////  update tables //////

        $inst = 1 ;
        $fields_query = tep_db_query("show fields from " . TABLE_PRODUCTS);
		while($row = tep_db_fetch_array($fields_query)) {
          if  ($row['Field'] == 'length_cm') {
           unset($inst) ;
              break;
          }
        }
        if(isset($inst)) {
          //  echo "new" ;
            \Yii::$app->db->createCommand("ALTER TABLE " .TABLE_PRODUCTS. " ADD `length_cm` FLOAT(6,2) NULL AFTER `products_weight`, ADD `height_cm` FLOAT(6,2) NULL AFTER `length_cm`, ADD `width_cm` FLOAT(6,2) NULL AFTER `height_cm`" )->execute() ;
        }
        else
        {
          //  echo "update" ;
            \Yii::$app->db->createCommand("ALTER TABLE " .TABLE_PRODUCTS. " CHANGE `length_cm` `length_cm` FLOAT(6,2), CHANGE `height_cm` `height_cm` FLOAT(6,2), CHANGE `width_cm`  `width_cm`  FLOAT(6,2)" )->execute() ;
        }
		// Increase the size of the set_function field in the platform config table to avoid a weird error during install
		\Yii::$app->db->createCommand("ALTER TABLE " .TABLE_PLATFORMS_CONFIGURATION. " CHANGE `set_function` `set_function` VARCHAR(550)" )->execute() ;
		return parent::install($platform_id);
    }

    // // BMH removal of module in admin
    function remove($platform_id)
    {
        global $db;
        \Yii::$app->db->createCommand("delete from " . TABLE_CONFIGURATION . " where configuration_key like 'MODULE_SHIPPING_AUPOST_%' ")->execute();
		\Yii::$app->db->createCommand("delete from " . TABLE_TRANSLATION . " where translation_key like '##MODULE_SHIPPING_AUPOST_%' ")->execute();
		return parent::remove($platform_id);
    }
    //  //  // BMH order of options loaded into admin-shipping
    function keys()
    {
        return array
        (
            'MODULE_SHIPPING_AUPOST_STATUS',
            'MODULE_SHIPPING_AUPOST_AUTHKEY',
            'MODULE_SHIPPING_AUPOST_SPCODE',
            'MODULE_SHIPPING_AUPOST_TYPE_LETTERS',
            'MODULE_SHIPPING_AUPOST_LETTER_HANDLING',
            'MODULE_SHIPPING_AUPOST_LETTER_PRIORITY_HANDLING',
            'MODULE_SHIPPING_AUPOST_LETTER_EXPRESS_HANDLING',
            'MODULE_SHIPPING_AUPOST_TYPES1',
            'MODULE_SHIPPING_AUPOST_RPP_HANDLING',
            'MODULE_SHIPPING_AUPOST_EXP_HANDLING',
            'MODULE_SHIPPING_AUPOST_PPS_HANDLING',
            'MODULE_SHIPPING_AUPOST_PPSE_HANDLING',
            //'MODULE_SHIPPING_AUPOST_PLAT_HANDLING',
            //'MODULE_SHIPPING_AUPOST_PLATSATCH_HANDLING',
            'MODULE_SHIPPING_AUPOST_COST_ON_ERROR',
            'MODULE_SHIPPING_AUPOST_HIDE_HANDLING',
            'MODULE_SHIPPING_AUPOST_DIMS',
            'MODULE_SHIPPING_AUPOST_WEIGHT_FORMAT',
            'MODULE_SHIPPING_AUPOST_ICONS',
            'MODULE_SHIPPING_AUPOST_DEBUG',
            'MODULE_SHIPPING_AUPOST_TARE',
            'MODULE_SHIPPING_AUPOST_SORT_ORDER',
            'MODULE_SHIPPING_AUPOST_TAX_CLASS',
        );
    }


// // // extra functions
    //// auspost API
    function get_auspost_api($url)
    {
        If (AUPOST_MODE == 'Test') {
            $aupost_url_apiKey = AUPOST_TESTMODE_AUTHKEY;
            }
            else {
            $aupost_url_apiKey = MODULE_SHIPPING_AUPOST_AUTHKEY;
            }
        if (APDEBUG2 == "Yes") {
            // echo '<br> ln1526 get_auspost_api $url= ' . $url;
            // echo '<br> ln1527 $aupost_url_apiKey= ' . $aupost_url_apiKey;
        }
    $crl = curl_init();
    $timeout = 5;
    // BMH changed to allow test key
    curl_setopt ($crl, CURLOPT_HTTPHEADER, array('AUTH-KEY:' . $aupost_url_apiKey)); // BMH new
    curl_setopt ($crl, CURLOPT_URL, $url);
    curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
    $ret = curl_exec($crl);
    // Check the response: if the body is empty then an error occurred
    if (APDEBUG2 == "Yes") {
        echo '<p class="aupost-debug"> <br> ln1539 $ret= ' . $ret .  '</p> ' ;// . '<br> var_dump = '; var_dump($ret);
        //$myarray=json_decode($ret);
       // echo '<br> ln1541 $myarray= '; print_r($myarray); echo '<br> '; var_dump($ret);
       // echo ' /p>';

    }
    //BMH 2023-01-23 added code for when Australia Post is down //BMH bof
    $edata = curl_exec($crl);   //  echo '<br> ln1546 $edata= ' . $edata; //BMH DEBUG
    $errtext = curl_error($crl);  //echo '<br> ln1547 $errtext= ' . $errtext; //BMH DEBUG
    $errnum = curl_errno($crl);   //echo '<br> ln1548 $errnum= ' . $errnum; //BMH DEBUG
    $commInfo = curl_getinfo($crl);   //echo '<br> ln1549 $commInfo= ' . $commInfo; //BMH DEBUG
    if ($edata === "Access denied") {
        $errtext = "<strong>" . $edata . ".</strong> Please report this error to <strong>System Owner ";
    }
    //BMH eof
    if(!$ret){
        die('<br>Error: "' . curl_error($crl) . '" - Code: ' . curl_errno($crl) .
            ' <br>Major Fault - Cannot contact Australia Post .
                Please report this error to System Owner. Then try the back button on you browser.');
    }

    curl_close($crl);
    return $ret;
    }
    // end auspost API


    function _handling($details,$currencies,$add,$aus_rate,$info)
    {
        if  (MODULE_SHIPPING_AUPOST_HIDE_HANDLING !='Yes') {
            $details = ' (Inc ' . $currencies->format($add / $aus_rate ). ' P &amp; H';  // Abbreviated for space saving in final quote format

            if ($info > 0)  {
            $details = $details." +$".$info." fee)." ;
            }
            else {
                $details = $details.")" ;
            }
        }
        return $details;
    }
	
	/**
     * configuration fields
     */
    public function configure_keys() {
        return array(
            'MODULE_SHIPPING_AUPOST_STATUS' => array(
                'title' => 'Enable this module?',
                'value' => 'True',
                'description' => 'Do you want to enable Aus Post shipping?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
			'MODULE_SHIPPING_AUPOST_TYPES1' => array(
                'title' => 'Shipping Methods for Australia',
                'value' => 'Regular Parcel, Regular Parcel +sig, Regular Parcel Insured +sig, Regular Parcel Insured (no sig), Prepaid Satchel, Prepaid Satchel +sig, Prepaid Satchel Insured +sig, Prepaid Satchel Insured (no sig), Express Parcel, Express Parcel +sig, Express Parcel Insured +sig, Express Parcel Insured (no sig), Prepaid Express Satchel, Prepaid Express Satchel +sig, Prepaid Express Satchel Insured +sig, Prepaid Express Satchel Insured (no sig)',
                'description' => 'Select the methods you wish to allow',
				'set_function' => 'tep_cfg_select_multioption(array(\'Regular Parcel\',\'Regular Parcel +sig\',\'Regular Parcel Insured +sig\',\'Regular Parcel Insured (no sig)\',\'Prepaid Satchel\',\'Prepaid Satchel +sig\',\'Prepaid Satchel Insured +sig\',\'Prepaid Satchel Insured (no sig)\',\'Express Parcel\',\'Express Parcel +sig\',\'Express Parcel Insured +sig\',\'Express Parcel Insured (no sig)\',\'Prepaid Express Satchel\',\'Prepaid Express Satchel +sig\',\'Prepaid Express Satchel Insured +sig\',\'Prepaid Express Satchel Insured (no sig)\'), ',
                'sort_order' => '4',
            ),
            'MODULE_SHIPPING_AUPOST_AUTHKEY' => array(
                'title' => 'Auspost API Key:',
                'value' => '',
                'description' => 'To use this module, you must obtain a 36 digit API Key from the <a href=\"https://developers.auspost.com.au/\" target=\"_blank\">Auspost Development Centre</a>',
                'sort_order' => '2',
            ),
            'MODULE_SHIPPING_AUPOST_SPCODE' => array(
                'title' => 'Dispatch Postcode',
                'value' => '0',
                'description' => 'Dispatch Postcode?',
                'sort_order' => '2',
            ),
			'MODULE_SHIPPING_AUPOST_TYPE_LETTERS' => array(
				'title' => '<hr>AustPost Letters (and small parcels@letter rates)',
				'value' => 'Aust Standard, Aust Priority, Aust Express, Aust Express +sig, Aust Express Insured +sig, Aust Express Insured (no sig)',
				'description' => 'Select the methods you wish to allow',
				'sort_order' => '3',
				'set_function' => 'tep_cfg_select_multioption(array(\'Aust Standard\',\'Aust Priority\',\'Aust Express\',\'Aust Express +sig\',\'Aust Express Insured +sig\',\'Aust Express Insured (no sig)\'), ',
            ),

			'MODULE_SHIPPING_AUPOST_SPCODE' => array(
                'title' => 'Dispatch Postcode',
                'value' => '0',
                'description' => 'Dispatch Postcode?',
                'sort_order' => '2',
            ),
			'MODULE_SHIPPING_AUPOST_LETTER_HANDLING' => array(
                'title' => 'Handling Fee - Standard Letters',
                'value' => '2.00',
                'description' => 'Handling Fee for Standard letters.',
                'sort_order' => '13',
            ),
			'MODULE_SHIPPING_AUPOST_LETTER_PRIORITY_HANDLING' => array(
                'title' => 'Handling Fee - Priority Letters',
                'value' => '3.00',
                'description' => 'Handling Fee for Priority letters.',
                'sort_order' => '13',
            ),
			'MODULE_SHIPPING_AUPOST_LETTER_EXPRESS_HANDLING' => array(
                'title' => 'Handling Fee - Express Letters',
                'value' => '2.00',
                'description' => 'Handling Fee for Express letters.',
                'sort_order' => '13',
            ),
			'MODULE_SHIPPING_AUPOST_RPP_HANDLING' => array(
                'title' => 'Handling Fee - Regular parcels',
                'value' => '2.00',
                'description' => 'Handling Fee Regular parcels.',
                'sort_order' => '6',
            ),
			'MODULE_SHIPPING_AUPOST_PPS_HANDLING' => array(
                'title' => 'Handling Fee - Prepaid Satchels',
                'value' => '2.00',
                'description' => 'Handling Fee for Prepaid Satchels.',
                'sort_order' => '7',
            ),
			'MODULE_SHIPPING_AUPOST_PPSE_HANDLING' => array(
                'title' => 'Handling Fee - Prepaid Satchels - Express',
                'value' => '2.00',
                'description' => 'Handling Fee for Prepaid Express Satchels.',
                'sort_order' => '8',
            ),
			'MODULE_SHIPPING_AUPOST_EXP_HANDLING' => array(
                'title' => 'Handling Fee - Express parcels',
                'value' => '2.00',
                'description' => 'Handling Fee for Express parcels.',
                'sort_order' => '9',
            ),
			'MODULE_SHIPPING_AUPOST_HIDE_HANDLING' => array(
                'title' => 'Hide Handling Fees?',
                'value' => 'No',
                'description' => 'The handling fees are still in the total shipping cost but the Handling Fee is not itemised on the invoice.',
				'set_function' => 'tep_cfg_select_option(array(\'Yes\', \'No\'), ',
                'sort_order' => '16',
            ),
			'MODULE_SHIPPING_AUPOST_DIMS' => array(
                'title' => 'Default Product Dimensions',
                'value' => '10,10,2',
                'description' => 'Default Product dimensions (in cm). Three comma separated values (eg 10,10,2 = 10cm x 10cm x 2cm). These are used if the dimensions of individual products are not set.',
                'sort_order' => '40',
            ),
			'MODULE_SHIPPING_AUPOST_EXP_HANDLING' => array(
                'title' => 'Handling Fee - Express parcels',
                'value' => '2.00',
                'description' => 'Handling Fee for Express parcels.',
                'sort_order' => '9',
            ),
			'MODULE_SHIPPING_AUPOST_COST_ON_ERROR' => array(
                'title' => 'Cost on Error',
                'value' => '99',
                'description' => 'If an error occurs this Flat Rate fee will be used.</br> A value of zero will disable this module on error.',
                'sort_order' => '20',
            ),
			'MODULE_SHIPPING_AUPOST_WEIGHT_FORMAT' => array(
                'title' => 'Parcel Weight format',
                'value' => 'gms',
                'description' => 'Are your store items weighted by grams or Kilos? (required so that we can pass the correct weight to the server',
				'set_function' => "tep_cfg_select_option(array('gms', 'kgs'), ",
                'sort_order' => '25',
            ),
			'MODULE_SHIPPING_AUPOST_ICONS' => array(
                'title' => 'Show AusPost logo?',
                'value' => 'Yes',
                'description' => 'Show Auspost logo in place of text?',
				'set_function' => 'tep_cfg_select_option(array(\'No\', \'Yes\'), ',
                'sort_order' => '19',
            ),
			'MODULE_SHIPPING_AUPOST_DEBUG' => array(
                'title' => 'Enable Debug?',
                'value' => 'No',
                'description' => 'See how parcels are created from individual items.</br>Shows all methods returned by the server, including possible errors. <strong>Do not enable in a production environment</strong>',
				'set_function' => 'tep_cfg_select_option(array(\'No\', \'Yes\'), ',
                'sort_order' => '40',
            ),
			'MODULE_SHIPPING_AUPOST_TARE' => array(
                'title' => 'Tare percent.',
                'value' => '10',
                'description' => 'Add this percentage of the items total weight as the tare weight. (This module ignores the global settings that seems to confuse many users. 10% seems to work pretty well.',
                'sort_order' => '50',
            ),
			'MODULE_SHIPPING_AUPOST_SORT_ORDER' => array(
                'title' => 'Sort order of display.',
                'value' => '0',
                'description' => 'Sort order of display. Lowest is displayed first.',
                'sort_order' => '55',
            ),
			'MODULE_SHIPPING_AUPOST_TAX_CLASS' => array(
                'title' => 'Tax Class',
                'value' => '0',
                'description' => 'Set Tax class or -none- if not registered for GST.',
				'use_function' => '\\common\\helpers\\Tax::get_tax_class_title',
				'set_function' => 'tep_cfg_pull_down_tax_classes(',
                'sort_order' => '55',
            ),
			'MODULE_SHIPPING_AUPOST_ORIGIN_ZIP' => array(
                'title' => 'Origin Postcode',
                'value' => '2000',
                'description' => 'Postcode where items will be sent from.',
                'sort_order' => '2',
            ),
        );
    }


    public function describe_status_key() {
        return new ModuleStatus('MODULE_SHIPPING_AUPOST_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_SHIPPING_AUPOST_SORT_ORDER');
    }
	function isOnline() {
        return true;
    }

}