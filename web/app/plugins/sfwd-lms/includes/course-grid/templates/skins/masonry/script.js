function learndash_course_grid_init_masonry( items_wrapper ) {
    const course_grid = items_wrapper.closest( '.learndash-course-grid' );
    const columns = parseInt( course_grid.dataset.columns );
    const min_width = parseInt( course_grid.dataset.min_column_width );
    
    const width = items_wrapper.offsetWidth;
    const items = items_wrapper.querySelectorAll( '.item' );

    if ( items.length < 1 ) {
        return;
    }

    const padding = 10;

    let max_columns = Math.floor( width / min_width );
    max_columns = max_columns > columns ? columns : max_columns;

    items.forEach( function( item ) {
        item.style.padding = padding + 'px';
        item.style.maxWidth = ( width / max_columns ) + 'px';
        item.style.width = ( width / max_columns ) + 'px';

        item.style.visibility = 'visible';
    } );

    const masonry = new Masonry( items_wrapper, {
        itemSelector: '.item',
        fitWidth: true,
        horizontalOrder: true,
    } );
}

function learndash_course_grid_init_masonry_responsive_design() {
    const wrappers = document.querySelectorAll( '.learndash-course-grid[data-skin="masonry"]' );
    wrappers.forEach( function( wrapper ) {
        const items_wrapper = wrapper.querySelector( '.items-wrapper.masonry' );
        
        learndash_course_grid_init_masonry( items_wrapper );
    } );
}

( function() {
    window.addEventListener( 'resize', function() {
        learndash_course_grid_init_masonry_responsive_design();
    } );
    
    window.addEventListener( 'load', function() {
        learndash_course_grid_init_masonry_responsive_design();
    } );
} )();