/**
* Responsive design
*/
function learndash_course_grid_init_grid_responsive_design() {
    const wrappers  = document.querySelectorAll( '.learndash-course-grid[data-skin="grid"]' );

    wrappers.forEach( function( wrapper ) {
        const items_wrapper = wrapper.querySelector( '.items-wrapper.grid' );

        if ( ! items_wrapper ) {
            return;
        }

        const first_item = items_wrapper.firstElementChild;

        if ( ! first_item ) {
            return;
        }

        // Set columns
        const min_width = wrapper.dataset.min_column_width;

        let columns = wrapper.dataset.columns;
        columns = parseInt( columns );
        let new_columns = wrapper.dataset.new_columns || columns;
        new_columns = parseInt( new_columns );
        
        const wrapper_width = wrapper.offsetWidth;
        let upper_columns = new_columns + 1;
        upper_columns = upper_columns > columns ? columns : upper_columns;
        const min_item_width = wrapper_width / upper_columns; 
        
        new_columns = Math.floor( wrapper_width / min_width );
        
        let item_width = first_item.offsetWidth;
        
        if ( item_width < min_width ) {
            new_columns = new_columns > columns ? columns : new_columns;
            new_columns = new_columns < 1 ? 1 : new_columns;
            wrapper.dataset.new_columns = new_columns;
            items_wrapper.style.gridTemplateColumns = 'repeat( ' + new_columns + ', minmax( 0, 1fr ) )';
        } else if ( min_item_width > min_width ) {
            new_columns = new_columns > columns ? columns : new_columns;
            new_columns = new_columns < 1 ? 1 : new_columns;
            wrapper.dataset.new_columns = new_columns;
            items_wrapper.style.gridTemplateColumns = 'repeat( ' + new_columns + ', minmax( 0, 1fr ) )';
        }
        
        const items = items_wrapper.querySelectorAll( '.item' );
        items.forEach( function( item ) {
            // Display item
            item.style.visibility = 'visible';
        } );
    } );
}

( function() { 
    window.addEventListener( 'resize', function() {
        learndash_course_grid_init_grid_responsive_design();
    } );
    
    window.addEventListener( 'load', function() {
        learndash_course_grid_init_grid_responsive_design();
    } );
} )();
