/******/ (() => { // webpackBootstrap
/*!*********************************!*\
  !*** ./src/assets/js/script.js ***!
  \*********************************/
(function () {
  // eslint-disable-next-line camelcase
  function ajax_apply_filter(course_grid, filter) {
    const data = {
      action: 'ld_cg_apply_filter',
      // eslint-disable-next-line camelcase, no-undef
      nonce: LearnDash_Course_Grid.nonce.load_posts,
      filter: prepare_filter(filter),
      course_grid: {
        // eslint-disable-next-line camelcase
        ...course_grid.dataset
      }
    };
    data.filter = JSON.stringify(data.filter);
    data.course_grid = JSON.stringify(data.course_grid);

    // eslint-disable-next-line camelcase, no-undef
    fetch(LearnDash_Course_Grid.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams(data)
    }).then(response => {
      return response.json();
    })
    // eslint-disable-next-line no-shadow
    .then(data => {
      if (typeof data !== 'undefined') {
        // eslint-disable-next-line eqeqeq
        if (data.status == 'success') {
          // eslint-disable-next-line camelcase
          const items_wrapper =
          // eslint-disable-next-line camelcase
          course_grid.querySelector('.items-wrapper');

          // eslint-disable-next-line camelcase
          items_wrapper.style.visibility = 'hidden';
          // eslint-disable-next-line camelcase
          items_wrapper.innerHTML = data.html;

          // eslint-disable-next-line camelcase
          course_grid.dataset.page = data.page;
          const pagination =
          // eslint-disable-next-line camelcase
          course_grid.querySelector('.pagination');
          if (!pagination) {
            // eslint-disable-next-line camelcase
            course_grid.insertAdjacentHTML('beforeend', data.html_pagination);
          }

          // eslint-disable-next-line eqeqeq
          if (data.html_pagination == '') {
            // eslint-disable-next-line no-shadow
            const pagination =
            // eslint-disable-next-line camelcase
            course_grid.querySelector('.pagination');
            if (pagination) {
              pagination.remove();
            }
          }

          // eslint-disable-next-line camelcase, eqeqeq
          if (course_grid.dataset.skin == 'grid') {
            setTimeout(function () {
              // eslint-disable-next-line no-undef
              learndash_course_grid_init_grid_responsive_design();
            }, 500);
            // eslint-disable-next-line camelcase, eqeqeq
          } else if (course_grid.dataset.skin == 'masonry') {
            setTimeout(function () {
              // eslint-disable-next-line no-undef
              learndash_course_grid_init_masonry(
              // eslint-disable-next-line camelcase
              course_grid.querySelector('.masonry'));
            }, 500);
          } else {
            setTimeout(function () {
              // eslint-disable-next-line camelcase
              items_wrapper.style.visibility = 'visible';
            }, 500);
          }
        }
      }
    }).catch(error => {
      // eslint-disable-next-line no-console
      console.log(error);
    });
  }

  // eslint-disable-next-line camelcase
  function ajax_init_infinite_scrolling(el) {
    const wrapper = el.closest('.learndash-course-grid');
    if (!wrapper) {
      // eslint-disable-next-line camelcase
      infinite_scroll_run = false;
      return false;
    }
    const filter = document.querySelector('.learndash-course-grid-filter[data-course_grid_id="' + wrapper.id + '"]');
    const data = {
      action: 'ld_cg_load_more',
      // eslint-disable-next-line camelcase, no-undef
      nonce: LearnDash_Course_Grid.nonce.load_posts,
      course_grid: JSON.stringify(wrapper.dataset),
      filter: JSON.stringify(prepare_filter(filter))
    };
    fetch(
    // eslint-disable-next-line camelcase, no-undef
    LearnDash_Course_Grid.ajaxurl + '?' + new URLSearchParams(data), {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    }).then(response => {
      return response.json();
    })
    // eslint-disable-next-line no-shadow
    .then(data => {
      if (typeof data !== 'undefined') {
        // eslint-disable-next-line eqeqeq
        if (data.status == 'success') {
          wrapper.querySelector('.items-wrapper').insertAdjacentHTML('beforeend', data.html);
          if (data.page !== 'complete') {
            wrapper.dataset.page = data.page;
          }

          // eslint-disable-next-line eqeqeq
          if (wrapper.dataset.pagination == 'infinite') {
            // eslint-disable-next-line camelcase
            infinite_scroll_run = false;
          }

          // eslint-disable-next-line eqeqeq
          if (data.page == 'complete') {
            const pagination = wrapper.querySelector('.pagination');
            pagination.remove();
          }

          // eslint-disable-next-line eqeqeq
          if (wrapper.dataset.skin == 'grid') {
            setTimeout(function () {
              // eslint-disable-next-line no-undef
              learndash_course_grid_init_grid_responsive_design();
            }, 500);
            // eslint-disable-next-line eqeqeq
          } else if (wrapper.dataset.skin == 'masonry') {
            wrapper.style.visibility = 'hidden';
            setTimeout(function () {
              // eslint-disable-next-line no-undef
              learndash_course_grid_init_masonry(wrapper.querySelector('.masonry'));
              wrapper.style.visibility = 'visible';
            }, 500);
          }
        }
      }
    }).catch(error => {
      // eslint-disable-next-line no-console
      console.log(error);
    });
  }

  // eslint-disable-next-line camelcase
  function in_viewport(element) {
    const pos = element.getBoundingClientRect();
    // eslint-disable-next-line no-undef
    return !(pos.top > innerHeight || pos.bottom < 0);
  }

  // eslint-disable-next-line camelcase
  function prepare_filter(filter) {
    const data = {};
    if (!filter) {
      return data;
    }
    const search = filter.querySelector('input[name="search"]');
    // eslint-disable-next-line camelcase
    const price_min = filter.querySelector('[name="price_min"]');
    // eslint-disable-next-line camelcase
    const price_max = filter.querySelector('[name="price_max"]');
    data.search = search ? search.value : null;
    let taxonomies = filter.dataset.taxonomies;
    taxonomies = taxonomies.split(',').map(function (value) {
      return value.trim();
    });

    // eslint-disable-next-line camelcase
    data.price_min = price_min ? price_min.value : null;
    // eslint-disable-next-line camelcase
    data.price_max = price_max ? price_max.value : null;
    taxonomies.forEach(function (taxonomy) {
      const inputs = filter.querySelectorAll('input[name="' + taxonomy + '[]"]:checked');
      const values = [];
      inputs.forEach(function (input) {
        values.push(input.value);
      });
      data[taxonomy] = values;
    });
    return data;
  }

  // Toggle filter display handler
  document.addEventListener('click', function (e) {
    const el = e.target;
    if (el.matches('.learndash-course-grid .toggle-filter')) {
      if (el.nextElementSibling.style.display === 'none' || el.nextElementSibling.style.display === '') {
        el.classList.remove('closed');
        el.classList.add('opened');
        el.nextElementSibling.style.display = 'block';
      } else {
        el.classList.remove('opened');
        el.classList.add('closed');
        el.nextElementSibling.style.display = 'none';
      }
    }
  });

  // Apply filter handler
  // eslint-disable-next-line camelcase
  const filter_submit = document.querySelectorAll('.learndash-course-grid-filter .button.apply');

  // eslint-disable-next-line camelcase
  if (filter_submit) {
    // eslint-disable-next-line camelcase
    filter_submit.forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        const filter = this.closest('.learndash-course-grid-filter');
        if (filter) {
          // eslint-disable-next-line camelcase
          const course_grid = document.getElementById(filter.dataset.course_grid_id);
          ajax_apply_filter(course_grid, filter);
        }
        if (filter.previousElementSibling && filter.previousElementSibling.classList.contains('toggle-filter')) {
          filter.previousElementSibling.classList.remove('opened');
          filter.previousElementSibling.classList.add('closed');
          filter.style.display = 'none';
        }
      });
    });
  }

  // Clear filter handler
  // eslint-disable-next-line camelcase
  const filter_clear = document.querySelectorAll('.learndash-course-grid-filter .button.clear');

  // eslint-disable-next-line camelcase
  if (filter_clear) {
    // eslint-disable-next-line camelcase
    filter_clear.forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        const filter = this.closest('.learndash-course-grid-filter');
        if (filter) {
          const search = filter.querySelector('input[name="search"]');
          // eslint-disable-next-line camelcase
          const price_min = filter.querySelector('input[name="price_min"]');
          // eslint-disable-next-line camelcase
          const price_max = filter.querySelector('input[name="price_max"]');
          // eslint-disable-next-line camelcase
          const price_min_range = filter.querySelector('input[name="price_min_range"]');
          // eslint-disable-next-line camelcase
          const price_max_range = filter.querySelector('input[name="price_max_range"]');
          if (search) {
            filter.querySelector('input[name="search"]').value = '';
          }

          // eslint-disable-next-line camelcase
          if (price_min) {
            filter.querySelector('input[name="price_min"]').value = '';
          }

          // eslint-disable-next-line camelcase
          if (price_max) {
            filter.querySelector('input[name="price_max"]').value = '';
          }

          // eslint-disable-next-line camelcase
          if (price_min_range) {
            filter.querySelector('input[name="price_min_range"]').value = '';
          }

          // eslint-disable-next-line camelcase
          if (price_max_range) {
            filter.querySelector('input[name="price_max_range"]').value = '';
          }
          filter.dataset.taxonomies.split(',').forEach(function (taxonomy) {
            taxonomy = taxonomy.trim();

            // eslint-disable-next-line eqeqeq
            if (taxonomy != '') {
              filter.querySelectorAll('input[name="' + taxonomy + '[]"]:not([disabled])').forEach(function (input) {
                input.checked = false;
              });
            }
          });

          // eslint-disable-next-line camelcase
          const course_grid = document.getElementById(filter.dataset.course_grid_id);
          ajax_apply_filter(course_grid, filter);
          if (filter.previousElementSibling && filter.previousElementSibling.classList.contains('toggle-filter')) {
            filter.previousElementSibling.classList.remove('opened');
            filter.previousElementSibling.classList.add('closed');
            filter.style.display = 'none';
          }
        }
      });
    });
  }

  // Dynamic input value update for price filter inputs
  document.addEventListener('input', function (e) {
    if (e.target.classList.contains('range')) {
      const name = e.target.name,
        value = e.target.value,
        // eslint-disable-next-line camelcase
        price_wrapper = e.target.closest('.filter');
      switch (name) {
        case 'price_min_range':
          // eslint-disable-next-line camelcase
          price_wrapper.querySelector('[name="price_min"]').value = value;
          break;
        case 'price_max_range':
          // eslint-disable-next-line camelcase
          price_wrapper.querySelector('[name="price_max"]').value = value;
          break;
      }
    }
    if (e.target.closest('.number-wrapper') !== null &&
    // eslint-disable-next-line eqeqeq
    e.target.type == 'number') {
      const name = e.target.name,
        value = e.target.value,
        // eslint-disable-next-line camelcase
        price_wrapper = e.target.closest('.filter');
      switch (name) {
        case 'price_min':
          // eslint-disable-next-line camelcase
          price_wrapper.querySelector('[name="price_min_range"]').value = value;
          break;
        case 'price_max':
          // eslint-disable-next-line camelcase
          price_wrapper.querySelector('[name="price_max_range"]').value = value;
          break;
      }
    }
  });

  /**
   * Pagination
   */

  // Load more button pagination handler
  document.addEventListener('click', function (e) {
    const el = e.target;
    if (!el.matches('.learndash-course-grid[data-pagination="button"] .pagination .load-more')) {
      return;
    }
    e.preventDefault();
    const wrapper = el.closest('.learndash-course-grid');
    const filter = document.querySelector('.learndash-course-grid-filter[data-course_grid_id="' + wrapper.id + '"]');
    const data = {
      action: 'ld_cg_load_more',
      // eslint-disable-next-line camelcase, no-undef
      nonce: LearnDash_Course_Grid.nonce.load_posts,
      course_grid: JSON.stringify(wrapper.dataset),
      filter: JSON.stringify(prepare_filter(filter))
    };

    // AJAX request
    fetch(
    // eslint-disable-next-line camelcase, no-undef
    LearnDash_Course_Grid.ajaxurl + '?' + new URLSearchParams(data), {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    }).then(response => {
      return response.json();
    })
    // eslint-disable-next-line no-shadow
    .then(data => {
      if (typeof data !== 'undefined') {
        // eslint-disable-next-line eqeqeq
        if (data.status == 'success') {
          // eslint-disable-next-line camelcase
          const items_wrapper = wrapper.querySelector('.items-wrapper');

          // eslint-disable-next-line camelcase
          items_wrapper.insertAdjacentHTML('beforeend', data.html);
          if (data.page !== 'complete') {
            wrapper.dataset.page = data.page;
          }

          // eslint-disable-next-line eqeqeq
          if (data.page == 'complete') {
            const pagination = wrapper.querySelector('.pagination');
            if (pagination) {
              pagination.remove();
            }
          }
          if (
          // eslint-disable-next-line eqeqeq
          wrapper.dataset.skin == 'grid' &&
          // eslint-disable-next-line eqeqeq
          data.html != '') {
            setTimeout(function () {
              // eslint-disable-next-line no-undef
              learndash_course_grid_init_grid_responsive_design();
            }, 500);
          } else if (
          // eslint-disable-next-line eqeqeq
          wrapper.dataset.skin == 'masonry' &&
          // eslint-disable-next-line eqeqeq
          data.html != '') {
            wrapper.style.visibility = 'hidden';
            setTimeout(function () {
              // eslint-disable-next-line no-undef
              learndash_course_grid_init_masonry(wrapper.querySelector('.masonry'));
              wrapper.style.visibility = 'visible';
            }, 500);
          }
        }
      }
    }).catch(error => {
      // eslint-disable-next-line no-console
      console.log(error);
    });
  });

  // Infinite scrolling handler
  // eslint-disable-next-line camelcase
  let infinite_scroll_run = false;
  document.addEventListener('scroll', function () {
    // eslint-disable-next-line camelcase
    const infinite_scroll_elements = document.querySelectorAll('.learndash-course-grid[data-pagination="infinite"] .pagination');

    // eslint-disable-next-line camelcase
    if (infinite_scroll_elements) {
      // eslint-disable-next-line camelcase
      infinite_scroll_elements.forEach(function (infinite_scroll) {
        // Make sure the function is called only once
        // eslint-disable-next-line camelcase
        if (in_viewport(infinite_scroll) && !infinite_scroll_run) {
          // eslint-disable-next-line camelcase
          infinite_scroll_run = true;
          ajax_init_infinite_scrolling(infinite_scroll);
        }
      });
    }
  });
})();
/******/ })()
;
//# sourceMappingURL=script.js.map