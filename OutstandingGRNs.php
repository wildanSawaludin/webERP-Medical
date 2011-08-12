<?php

/*$Id$ */

/* $Revision: 1.11 $ */

include('includes/session.inc');

If (isset($_POST['PrintPDF'])
	AND isset($_POST['FromCriteria'])
	AND strlen($_POST['FromCriteria'])>=1
	AND isset($_POST['ToCriteria'])
	AND strlen($_POST['ToCriteria'])>=1){

	include('includes/PDFStarter.php');
	$pdf->addInfo('Title',_('Outstanding GRNs Report'));
	$pdf->addInfo('Subject',_('Outstanding GRNs Valuation'));
	$FontSize=10;
	$PageNumber=1;
	$line_height=12;
	$Left_Margin=30;

	  /*Now figure out the data to report for the criteria under review */

	$SQL = "SELECT grnno,
					orderno,
					grns.supplierid,
					suppliers.suppname,
					grns.itemcode,
					grns.itemdescription,
					qtyrecd,
					quantityinv,
					grns.stdcostunit,
					actprice,
					unitprice
				FROM grns,
					purchorderdetails,
					suppliers
				WHERE grns.supplierid=suppliers.supplierid
					AND grns.podetailitem = purchorderdetails.podetailitem
					AND qtyrecd-quantityinv>0
					AND grns.supplierid >='" . $_POST['FromCriteria'] . "'
					AND grns.supplierid <='" . $_POST['ToCriteria'] . "'
				ORDER BY supplierid,
						grnno";

	$GRNsResult = DB_query($SQL,$db,'','',false,false);

	if (DB_error_no($db) !=0) {
	  $title = _('Outstanding GRN Valuation') . ' - ' . _('Problem Report');
	  include('includes/header.inc');
	  prnMsg(_('The outstanding GRNs valuation details could not be retrieved by the SQL because') . ' - ' . DB_error_msg($db),'error');
	   echo '<br /><a href="' .$rootpath .'/index.php">' . _('Back to the menu') . '</a>';
	   if ($debug==1){
		  echo '<br />'.$SQL;
	   }
	   include('includes/footer.inc');
	   exit;
	}

	if (DB_num_rows($GRNsResult) == 0) {
	  $title = _('Outstanding GRN Valuation') . ' - ' . _('Problem Report');
	  include('includes/header.inc');
	  prnMsg(_('No outstanding GRNs valuation details retrieved'), 'warn');
	   echo '<br /><a href="' .$rootpath .'/index.php">' . _('Back to the menu') . '</a>';
	   if ($debug==1){
		  echo '<br />'.$SQL;
	   }
	   include('includes/footer.inc');
	   exit;
	}

	include ('includes/PDFOstdgGRNsPageHeader.inc');

	$Tot_Val=0;
	$Supplier = '';
	$SuppTot_Val=0;
	While ($GRNs = DB_fetch_array($GRNsResult,$db)){

		if ($Supplier!=$GRNs['supplierid']){

			if ($Supplier!=''){ /*Then it's NOT the first time round */
				/* need to print the total of previous supplier */
				$YPos -= (2*$line_height);
				$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,_('Total for') . ' ' . $Supplier . ' - ' . $SupplierName);
				$DisplaySuppTotVal = number_format($SuppTot_Val,2);
				$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplaySuppTotVal, 'right');
				$YPos -=$line_height;
				$pdf->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
				$YPos -=(2*$line_height);
				$SuppTot_Val=0;
			}
			$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,$GRNs['supplierid'] . ' - ' . $GRNs['suppname']);
			$Supplier = $GRNs['supplierid'];
			$SupplierName = $GRNs['suppname'];
		}
		$YPos -=$line_height;

		$LeftOvers = $pdf->addTextWrap(32,$YPos,40,$FontSize,$GRNs['grnno']);
		$LeftOvers = $pdf->addTextWrap(70,$YPos,40,$FontSize,$GRNs['orderno']);
		$LeftOvers = $pdf->addTextWrap(110,$YPos,200,$FontSize,$GRNs['itemcode'] . ' - ' . $GRNs['itemdescription']);
		$DisplayStdCost = number_format($GRNs['stdcostunit'],2);
		$DisplayQtyRecd = number_format($GRNs['qtyrecd'],2);
		$DisplayQtyInv = number_format($GRNs['quantityinv'],2);
		$DisplayQtyOstg = number_format($GRNs['qtyrecd']- $GRNs['quantityinv'],2);
		$LineValue = ($GRNs['qtyrecd']- $GRNs['quantityinv'])*$GRNs['stdcostunit'];
		$DisplayValue = number_format($LineValue,2);

		$LeftOvers = $pdf->addTextWrap(310,$YPos,50,$FontSize,$DisplayQtyRecd,'right');
		$LeftOvers = $pdf->addTextWrap(360,$YPos,50,$FontSize,$DisplayQtyInv, 'right');
		$LeftOvers = $pdf->addTextWrap(410,$YPos,50,$FontSize,$DisplayQtyOstg, 'right');
		$LeftOvers = $pdf->addTextWrap(460,$YPos,50,$FontSize,$DisplayStdCost, 'right');
		$LeftOvers = $pdf->addTextWrap(510,$YPos,50,$FontSize,$DisplayValue, 'right');

		$Tot_Val += $LineValue;
		$SuppTot_Val += $LineValue;

		if ($YPos < $Bottom_Margin + $line_height){
		   include('includes/PDFOstdgGRNsPageHeader.inc');
		}

	} /*end while loop */


/*Print out the supplier totals */
	$YPos -=$line_height;
	$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,_('Total for') . ' ' . $Supplier . ' - ' . $SupplierName, 'left');

	$DisplaySuppTotVal = number_format($SuppTot_Val,2);
	$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplaySuppTotVal, 'right');

	/*draw a line under the SUPPLIER TOTAL*/
	$pdf->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
	$YPos -=(2*$line_height);

	$YPos -= (2*$line_height);

/*Print out the grand totals */
	$LeftOvers = $pdf->addTextWrap(80,$YPos,260-$Left_Margin,$FontSize,_('Grand Total Value'), 'right');
	$DisplayTotalVal = number_format($Tot_Val,2);
	$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplayTotalVal, 'right');
	$pdf->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
	$YPos -=(2*$line_height);

	$pdf->OutputD($_SESSION['DatabaseName'] . '_OSGRNsValuation_' . date('Y-m-d').'.pdf');//UldisN
	$pdf->__destruct(); //UldisN
} else { /*The option to print PDF was not hit */

	$title=_('Outstanding GRNs Report');
	include('includes/header.inc');

	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $title . '</p>';

	echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post"><table class="selection">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<tr><td>' . _('From Supplier Code') . ':</td>
		<td><input type="text" name="FromCriteria" value="0" /></td></tr>';
	echo '<tr><td>' . _('To Supplier Code'). ':</td>
		<td><input type="text" name="ToCriteria" value="zzzzzzz" /></td></tr>';

	echo '</table><br /><div class="centre"><input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" /></div>';

	include('includes/footer.inc');

} /*end of else not PrintPDF */

?>