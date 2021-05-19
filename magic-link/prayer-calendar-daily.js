jQuery(document).ready(function($){
  clearInterval(window.fiveMinuteTimer)
  set_title_url()
  window.get_list()

  $('.filter_link').on('click', function(e){
    let metavalue = $(this).data('meta-value')
    let posttype = $(this).data('posttype')
    $('#content').empty()
    $('.loading-spinner').addClass('active')
    window.get_filter( posttype, metavalue )
    $('#offCanvasLeft').foundation('close')
  })
  $('.basic_lists').on('click', function(e){
    let type = $(this).data('type')
    console.log(type)
  })

  // $('#drag-refresh').draggable({
  //     axis: "y",
  //     revert: true,
  //     start: function(e){
  //         jQuery('.loading-spinner').addClass('active')
  //         window.get_list()
  //     }
  // })

})

function set_title_url(){
  jQuery('#title_link').prop('href', '/' + jsObject.parts.root + '/' + jsObject.parts.type + '/' + jsObject.parts.public_key )
}

window.get_list = () => {
  jQuery.ajax({
    type: "POST",
    data: JSON.stringify({ action: 'get', parts: jsObject.parts }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
    }
  })
    .done(function(data){
      window.load_list( data )
    })
    .fail(function(e) {
      console.log(e)
      jQuery('#error').html(e)
    })

  // jQuery.ajax({
  //     type: "POST",
  //     data: JSON.stringify({ action: 'filter_list', parts: jsObject.parts }),
  //     contentType: "application/json; charset=utf-8",
  //     dataType: "json",
  //     url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
  //     beforeSend: function (xhr) {
  //         xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
  //     }
  // })
  //     .done(function(data){
  //         window.load_filter_list( data )
  //     })
  //     .fail(function(e) {
  //         console.log(e)
  //         jQuery('#error').html(e)
  //     })
}

window.log_prayer_action = ( post_id ) => {
  // note parts.post_id is the user_id, not the post_id
  jQuery.ajax({
    type: "POST",
    data: JSON.stringify({ action: 'log', parts: jsObject.parts, post_id: post_id }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
    }
  })
    .done(function(data){
      console.log(data)
    })
    .fail(function(e) {
      console.log(e)
    })
}

window.load_list = ( data ) => {
  let content = jQuery('#content')
  let spinner = jQuery('.loading-spinner')
  let icon = ''
  content.empty()
  jQuery.each(data.lists, function(i,v){
    let label = i.replace('_', ' ')
    content.append(`<div class="cell center" style="background:whitesmoke;text-transform:capitalize;">${label} <span>(${data.counts[i]})</span></div>`)

    jQuery.each(v, function(ii,vv){
      icon = ''
      if ( 'contacts' === vv.post_type ) {
        icon = 'fi-torso'
      } else if ( 'groups' === vv.post_type ) {
        icon = 'fi-torsos-all'
      } else if ( 'trainings' === vv.post_type ) {
        icon = 'fi-results-demographics'
      }

      content.append(`<div class="cell prayer-list-wrapper">
                        <div class="draggable ui-widget-content prayer-list" data-value="${vv.post_id}" id="item-${vv.post_id}">
                            <i class="${icon}"></i> <span class="item-name">${vv.name}</span>
                        </div>
                     </div>
      `)
    })
  })

  let prayer_list = jQuery('.prayer-list')
  prayer_list.draggable({
    axis: "x",
    revert: true,
    stop: function(e) {
      let v = jQuery(this).data('value')
      window.log_prayer_action(v)
      jQuery('#item-'+v).addClass('checked-off')
    }
  })
  // prayer_list.click(function(e){
  //   let v = jQuery(this).data('value')
  //   window.log_prayer_action(v)
  //   jQuery('#item-'+v).addClass('checked-off')
  // })

  spinner.removeClass('active')

}

window.load_filter_list = ( data ) => {

}

window.get_filter = ( posttype, metavalue ) => {
  jQuery.ajax({
    type: "POST",
    data: JSON.stringify({ action: 'filter', parts: jsObject.parts, post_type: posttype, meta_value: metavalue }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
    }
  })
    .done(function(data){
      window.load_list( data )
    })
    .fail(function(e) {
      console.log(e)
    })
}
