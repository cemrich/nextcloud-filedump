(function ($, OC) {

	var btnCreateBackup;

	$(document).ready(function () {
		btnCreateBackup = $('#filedump-backup-button');
		btnCreateBackup.click(onBackupClick);
	});

	function onBackupClick() {
		btnCreateBackup.attr('disabled', 'disabled');
		OCdialogs.confirm(
			t('filedump_backup', 'Are you sure you want to create a new backup?'),
			t('filedump_backup', 'Create backup?'),
			onBackupDialogConfirmation,
			true
		);
	}

	function onBackupDialogConfirmation(confirmed) {
		if (confirmed) {
			var url = OC.generateUrl('/apps/filedump/create-backup');
			$.post(url).success(onBackupResponse);
		} else {
			btnCreateBackup.removeAttr('disabled');
		}
	}

	function onBackupResponse(response) {
		OCdialogs.info(response.message, t('filedump_backup', 'New backup'), null, true);
		btnCreateBackup.removeAttr('disabled');
	}

})(jQuery, OC);
