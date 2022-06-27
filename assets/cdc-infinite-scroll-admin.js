let optionForm = document.getElementById("cdc-infinite-scroll-option-form");

function IsJsonString(str) {
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	return true;
}

if( optionForm ){
	optionForm.addEventListener("submit", function(e){
		e.preventDefault();
		if( e.submitter.id === 'submit-btn' ){
			let elements = Array.from(optionForm.elements);
			elements.forEach(element => {
				if( element.tagName === 'TEXTAREA' && ! IsJsonString(element.value)){

				}
			});
			optionForm.submit();
		} else if( e.submitter.id === 'cdc-infinite-scroll-reset'){
			let elements = Array.from(optionForm.elements);
			elements.forEach(element => {
				if( element.hasAttribute( 'data-default' ) ){
					if( element.tagName === 'TEXTAREA' ){
						let dataDefaultObj = JSON.parse( element.getAttribute( 'data-default' ) );
						element.value = JSON.stringify( dataDefaultObj, null, 2 );
					} else {
						element.value = element.getAttribute( 'data-default' );
					}
				}
			});
		}
	});
}
