<?

$percents_in = createOptions('Select One', 'no copay', '10% of in-network fee', '20% of in-network fee', '30% of in-network fee', '40% of in-network fee', '50% of in-network fee', '60% of in-network fee');
$percents_out = createOptions('Select One', '0% of out-of-network fee', '10% of out-of-network fee', '20% of out-of-network fee', '30% of out-of-network fee', '40% of out-of-network fee', '50% of out-of-network fee', '60% of out-of-network fee');
$percents_out2 = createOptions('Select One', 'Not Covered', '0% of out-of-network fee', '10% of out-of-network fee', '20% of out-of-network fee', '30% of out-of-network fee', '40% of out-of-network fee', '50% of out-of-network fee', '60% of out-of-network fee');

$percent_ortho_in = createOptions('Select One', 'Not Covered', 'No copayment');
for($i=5; $i<=100; $i+=5) {
	$percent_ortho_in["$i% on in-network fee"] = "$i% on in-network fee";
}

$child_adult = createOptions('Select One', 'n/a', 'Child only', 'Adult and child');

$money = array();
for($i = 500; $i <= 5000; $i += 250) {
	$money[] = '$'.number_format($i, 0);
}
$ortho_max_in = createOptions('Select Amount', 'n/a', $money);
$ortho_max_out = createOptions('Select Amount', $money);

$months = array();
for($i=3; $i<=24; $i+=3) {
	$months[] = $i . ' months';
}
$months = createOptions('n/a', $months);

$CI =& get_instance();
$CI->_temp_customIndex = $customIndex;

function dd($id, $vals, $opts = array()) {
	$CI =& get_instance();
	$opts['type'] = 'dropdown';
	$opts['values'] = $vals;
	$opts['rules'] = array('required');
	return $CI->formidable->renderField("dentalblue_plan_{$CI->_temp_customIndex}_custom_$id", FALSE, $opts);
}

?>
	<tr>
		<td colspan="3">
			<table class="formTable servicesTable"><tbody>
				<tr>
					<td></td>
					<th class="inNet">In-Network</th>
					<th>Out-of-Network</th>
					<td></td>
				</tr>
				<tr>
					<td class="rowNote">The following are examples of what is and is not covered by your plan:</td>
					<td class="rowNote inNet">You Pay:</td>
					<td class="rowNote">You Pay:</td>
					<td></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label><strong>Diagnostic and Preventative Services:</strong></label></td>
					<td class="rowField inNet"><?= dd('diaprev_in', $percents_in) ?></td>
					<td class="rowField"><?= dd('diaprev_out', $percents_out) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr>
					<td class="rowLabel">&nbsp;</td>
					<td class="inNet"></td>
					<td></td>
					<td></td>
				</tr>
				<tr>
					<td class="rowLabel"><strong>Restorative Services, for example:</strong></td>
					<td class="inNet"></td>
					<td></td>
					<td></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Filling, amalgam, two surfaces (2150):</label></td>
					<td class="rowField inNet"><?= dd('filling_in', $percents_in) ?></td>
					<td class="rowField"><?= dd('filling_out', $percents_out2) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Oral surgery, e.g., toothe extraction, simple (7140):</label></td>
					<td class="rowField inNet"><?= dd('surg_in', $percents_in) ?></td>
					<td class="rowField"><?= dd('surg_out', $percents_out2) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Endodontics, e.g., root canal, molar (3330):</label></td>
					<td class="rowField inNet"><?= dd('endo_in', $percents_in) ?></td>
					<td class="rowField"><?= dd('endo_out', $percents_out2) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Periodontics, e.g., scaling and root planing per quadrant (4341):</label></td>
					<td class="rowField inNet"><?= dd('perio_in', $percents_in) ?></td>
					<td class="rowField"><?= dd('perio_out', $percents_out2) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Prosthodontics, e.g.:<br/>
						crown, porcelain fused to high noble metal (2750)<br/>
						denture, complete, upper or lower (5110/5120)</label></td>
					<td class="rowField inNet"><?= dd('prosth_in', $percents_in) ?></td>
					<td class="rowField"><?= dd('prosth_out', $percents_out2) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr>
					<td class="rowLabel">&nbsp;</td>
					<td class="inNet"></td>
					<td></td>
					<td></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label><strong>Orthodontic Services</strong></label></td>
					<td class="rowField inNet"><?= dd('ortho_in', $percent_ortho_in) ?></td>
					<td class="rowField"><?= dd('ortho_out', $percents_out2) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Child Only / Adult and Child </label></td>
					<td class="rowField inNet"><?= dd('chad_in', $child_adult) ?></td>
					<td class="rowField"><?= dd('chad_out', $child_adult) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr class="row">
					<td class="rowLabel"><label>Ortho lifetime maximum benefits </label></td>
					<td class="rowField inNet"><?= dd('orthomax_in', $ortho_max_in) ?></td>
					<td class="rowField"><?= dd('orthomax_out', $ortho_max_out) ?></td>
					<td class="rowError"></td>
				</tr>
				<tr>
					<td class="rowLabel">&nbsp;</td>
					<td class="inNet"></td>
					<td></td>
					<td></td>
				</tr>
				<tr>
					<td class="rowLabel"><strong>Waiting Periods</strong></td>
					<td class="inNet"></td>
					<td></td>
					<td></td>
				</tr>
<? foreach(array(
	'rest' => 'Minor Restorative Services',
	'surg' => 'Oral Surgery Services',
	'peri' => 'Periodontic Services',
	'endo' => 'Endodontic Services',
	'prosth' => 'Prosthodontic Services',
	'ortho' => 'Orthodontic Services'
) as $name => $label):
		$id = "dentalblue_plan_{$customIndex}_custom_wait_$name";
		?>
				<tr class="row">
					<td class="rowLabel">
						<label for="<?= $id ?>">
							<input type="checkbox" name="<?= $id ?>" id="<?= $id ?>" value="<?= $label ?>"/>
							<?= $label ?>
						</label>
					</td>
					<td class="rowField inNet"><?= dd('wait_'.$name.'_in', $months, array('onlyEnableIf' => $id)) ?></td>
					<td class="rowField"><?= dd('wait_'.$name.'_out', $months, array('onlyEnableIf' => $id)) ?></td>
					<td class="rowError"></td>
				</tr>
<? endforeach; ?>
			</tbody></table>
		</td>
	</tr>