let settings = cdcInfiniteScrollSettings;
let elem = document.querySelector(settings.queryClass);
if( elem !== null ){
	let posts = elem.getElementsByClassName(settings.postClass);
	let gutter = parseInt( window.getComputedStyle(posts[0]).marginRight, 10 );
	let elemClasses = [].slice.apply(elem.classList);
	var columnsEndOfRow = 0;
	let columnsClass = elemClasses.find(e => e.includes('columns-'));
	if( columnsClass !== undefined ){
		let columnsValue = parseInt( columnsClass.match(/\d+/) );
		columnsEndOfRow = columnsValue - 1 ? columnsValue - 1 : 0;
	}
	let msnry = {};
	if( settings.enableMasonry ){
		// init Masonry
		let masonryOptions = JSON.parse( settings.masonryOptions );
		if( masonryOptions.hasOwnProperty('columnWidth') && masonryOptions.columnWidth === 'posts[columnsEndOfRow]' ){
			masonryOptions.columnWidth = posts[columnsEndOfRow];
		}
		if( masonryOptions.hasOwnProperty('gutter') && masonryOptions.gutter === 'gutter' ){
			masonryOptions.gutter = gutter;
		}
		msnry = new Masonry( settings.queryClass, masonryOptions);
	}
	let scrollOptions = JSON.parse(settings.scrollOptions);
	if( scrollOptions.hasOwnProperty('outlayer') && scrollOptions.outlayer === "msnry" ){
		scrollOptions.outlayer = msnry;
	}
	let infScroll = new InfiniteScroll( elem, scrollOptions );
}

