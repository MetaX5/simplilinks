document.addEventListener("DOMContentLoaded", function(event) {
    if ( location.search === "?page=mmlinks" ) {
		document.getElementById('wybierz_link').addEventListener("click", function(event) {
			if ( document.querySelector('input[name=mm_id]').value == "" ) {
				event.preventDefault();
			}
		});
		if ( document.getElementById('mm_success') != null ) {
			document.getElementById('mm_success').style.opacity = 0;
		}
	}
	
    if ( document.querySelector('#frazy') !== null ) {
		var frazy = [];
		
		document.getElementById("submit_frazy").addEventListener("click", function(event) {
			if ( document.querySelector('#lista_fraz').querySelectorAll('li').length === 0 ) {
				event.preventDefault();
			}
		});
		
		document.getElementById("input_frazy").addEventListener("keypress", function(event) {
			if (event.keyCode === 13) {
				event.preventDefault();
				if ( event.target.value == "" ) {
					return;
				}
				var el = document.createElement('li');
				var btn_d = document.createElement('button');
				var el_text = document.createElement('span');
				btn_d.setAttribute( 'class', 'btn_d');
				el_text.innerText = event.target.value;
				document.querySelector('#lista_fraz').appendChild(el);
				document.querySelector('#lista_fraz li:last-child').appendChild(btn_d);
				document.querySelector('#lista_fraz li:last-child').appendChild(el_text);
				frazy.push( event.target.value );
				document.getElementById('frazy_array').setAttribute('value', frazy);
				event.target.value = null;
				document.querySelector('#lista_fraz li:last-child button').addEventListener('click', function(event) {
					event.preventDefault();
					event.target.parentElement.remove();
				});
				btn_d.addEventListener("click", function(event) {
					for( var i = 0; i < frazy.length; i++){ 
						if ( frazy[i] == event.target.parentElement.querySelector('span').innerText ) {
							frazy.splice(i, 1);
							document.getElementById('frazy_array').setAttribute('value', frazy);
						}
					}
				});
			}
		});	
	}
	
	if ( location.search === "?page=mmlinks_all" ) {
		const btn_array = document.querySelector('#tabela').querySelectorAll('button');
		
		for (let i = 0; i < btn_array.length; i++) {
			let current = btn_array[i];
			current.addEventListener("click", (e) => {
				e.target.setAttribute('name', 'wylosowane_usun');
				let id = e.target.parentElement.parentElement.previousElementSibling.previousElementSibling.previousElementSibling.innerText;
				e.target.setAttribute('value', id);
				document.getElementById('wylosowane').submit();
			});
		}
	}
});

function mm_Search() {
	var input, filter, table, tr, td, i, txtValue, x, b;
	b = true;
	input = document.getElementById("myInput");
	filter = input.value.toUpperCase();
	table = document.getElementById("tabela");
	tr = table.getElementsByTagName("tr");
	
	for (i = 0; i < tr.length; i++) {
		td = tr[i].getElementsByTagName("td")[1];
		x = tr[i].getElementsByTagName("td")[2];
		if ( td || x ) {
			txtValue = td.textContent || td.innerText;
			txtValue2 = x.textContent || x.innerText;
			if ( txtValue.toUpperCase().indexOf(filter) > -1 || txtValue2.toUpperCase().indexOf(filter) > -1 ) {
				tr[i].style.display = "";
				
				if ( b ) {
					tr[i].querySelectorAll('a')[0].style.opacity = "1";
					tr[i].querySelectorAll('a')[1].style.opacity = "1";
					b = false;
				}
			} else {
				tr[i].style.display = "none";
			}
		}
	}
	
	if ( filter == "" ) {
		for (i = 2; i < tr.length-1; i++) {
			if ( tr[i].querySelector('td:nth-child(2)') != null && tr[i+1].querySelector('td:nth-child(2)') != null && tr[i].querySelector('td:nth-child(2)').querySelectorAll('a') != null ) {
				if ( tr[i].querySelector('td:nth-child(2)').querySelectorAll('a')[0].innerText != tr[i+1].querySelector('td:nth-child(2)').querySelectorAll('a')[0].innerText ) {
					i++;
					continue;
				}
				tr[i].querySelector('td:nth-child(2)').querySelectorAll('a')[0].style.opacity = "0";
				tr[i].querySelector('td:nth-child(2)').querySelectorAll('a')[1].style.opacity = "0";
			}
		}
	}
}

if ( location.search === "?page=mmlinks_config" ) {
	if ( document.getElementById('mm_success2') != null ) {
		document.getElementById('mm_success2').style.opacity = 0;
	}
	jQuery(document).ready(function($){
		$('.my-color-field').wpColorPicker();
		
		$('.mm_va img:eq(0)').on('click', function() {
			$(this).addClass('mm-outline');
			$('.mm_va img:eq(1)').removeClass('mm-outline');
		});
		$('.mm_va img:eq(1)').on('click', function() {
			$(this).addClass('mm-outline');
			$('.mm_va img:eq(0)').removeClass('mm-outline');
		});
		
		$('#config-save').on('click', function(e) {
			var x =  $('.wp-color-result').attr('style').slice(0, -1);
			x = x.slice(18);
			console.log(x);
			$('#kolor').attr('value', x);
			if ( $('.mm_va img:eq(0)').hasClass('mm-outline') ) {
				$('#orientacja').attr('value', 'pionowo');
			} else {
				$('#orientacja').attr('value', 'poziomo');
			}
		});
	});
}