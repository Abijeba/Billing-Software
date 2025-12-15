
<?php
// utilities.php content starts here

/**
 * Converts a numeric amount to Indian Rupees in words.
 * (The full function code you received previously goes here)
 */
function numberToWords(float $number) : string {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundreds = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 
        50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 
        90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundreds = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundreds : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundreds;
        } else {
            $str [] = null;
        }
    }
    $Rupees = implode('', array_reverse($str));
    $Paise = '';
    if ($decimal) {
        $Paise = ($decimal < 21) ? $words[$decimal] . ' Paise' : $words[floor($decimal / 10) * 10] . ' ' . $words[$decimal % 10] . ' Paise';
    }
    $final_string = ($Rupees ? 'Rupees ' . $Rupees : '') . ($Paise ? ' and ' . $Paise : '') . ' Only.';
    return trim(str_replace('  ', ' ', $final_string));
}

// utilities.php content ends here
?>
