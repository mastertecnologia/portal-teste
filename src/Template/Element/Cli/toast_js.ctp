(function () {
	window.cliUiToast = function (message, kind) {
		kind = kind || 'info';
		var loader = '#1d9e75', bg = '#161b22';
		if (kind === 'error') { loader = '#f85149'; bg = '#2d1418'; }
		else if (kind === 'success') { loader = '#3fb950'; bg = '#0d2818'; }
		else if (kind === 'warning') { loader = '#d29922'; bg = '#2d2a1a'; }
		var heading = '';
		if (kind === 'error') heading = 'Erro';
		else if (kind === 'success') heading = 'Sucesso';
		else if (kind === 'warning') heading = 'Atenção';
		if (typeof $ !== 'undefined' && typeof $.toast === 'function') {
			$.toast({
				heading: heading,
				text: message,
				position: 'top-right',
				loaderBg: loader,
				bgColor: bg,
				textColor: '#e6edf3',
				hideAfter: kind === 'error' ? 6000 : 4000,
				showHideTransition: 'fade',
			});
		} else {
			window.alert(message);
		}
	};
})();
