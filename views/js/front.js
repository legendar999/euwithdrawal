/*
 * euwithdrawal - front office behaviour.
 * Toggles the per-item checkbox list when "Only selected items" is chosen.
 * @license AFL-3.0
 */
(function () {
	'use strict';
	function init() {
		var radios = document.querySelectorAll('.euw-scope-radio');
		var box = document.getElementById('euw-items');
		if (!radios.length || !box) {
			return;
		}
		function sync() {
			var checked = document.querySelector('.euw-scope-radio:checked');
			var showItems = checked && checked.value === 'items';
			box.style.display = showItems ? 'block' : 'none';
		}
		for (var i = 0; i < radios.length; i++) {
			radios[i].addEventListener('change', sync);
		}
		sync();
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
