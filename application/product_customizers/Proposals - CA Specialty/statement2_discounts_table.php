	<tr>
		<td colspan="3">
			<table class="statement_table" id="st2_statement_table">
				<tr class="headings">
					<th>&nbsp;</th>
					<th>Savings off your Anthem medical premium</th>
					<th>Approximate savings on Annual Health Premium</th>
				</tr>
<? foreach($types as $field => $info):
				list($label, $percent, $onlyIf) = $info;
?>
				<tr id="st2_h_<?= $field ?>" class="row" onlyShowIf="<?= $onlyIf ?>">
					<th><label for="st2_h_<?= $field ?>_enabled"><?= $this->formidable->renderField("st2_h_{$field}_enabled", FALSE, array('type' =>'checkbox', 'value' => TRUE)); ?> <?= $label ?></label></th>
					<td class="percent"><?= $percent ?>%</td>
					<td class="annual_savings">$<span id="st2_h_<?= $field ?>_annual_savings">0</span></td>
				</tr>
<? endforeach; ?>
<? /*
				<tr class="summary">
					<th>Total Savings Available</th>
					<td>4%</td>
					<td>0</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>

 */ ?>
			</table>
		</td>
	</tr>