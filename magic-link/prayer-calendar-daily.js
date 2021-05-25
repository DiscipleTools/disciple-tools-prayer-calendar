jQuery(document).ready(function($){
  clearInterval(window.fiveMinuteTimer)
  set_title_url()

  // load full prayer list data
  jQuery.ajax({
    type: "POST",
    data: JSON.stringify({ action: 'get_all', parts: jsObject.parts }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
    }
  })
    .done(function(data){
      window.current_list = data
      window.load_app()
    })
    .fail(function(e) {
      console.log(e)
      jQuery('#error').html(e)
    })
})

function set_title_url(){
  jQuery('#title_link').prop('href', '/' + jsObject.parts.root + '/' + jsObject.parts.type + '/' + jsObject.parts.public_key )
}

window.load_app = () => {
  let data = window.current_list
  console.log(data)
  let content = jQuery('#content')
  content.empty()

  let spinner = jQuery('.loading-spinner')
  spinner.addClass('active')

  window.load_list( window.prepare_list( 'today' ) )

  // load menu filter
  let menu = {}
  window.load_menu( data )

  spinner.removeClass('active')
}

window.prepare_list = ( type, sort = ['name'], order = ['asc'] ) => {
  let data = window.current_list
  let list = {
    label: type.replace('_', ' '),
    count: 0,
    list: []
  }


  jQuery.each(data.list, function(i,v){
    v.meta = ''
  })

  switch (type ) {
    case 'today_desc':
    case 'today':
      var d = new Date();
      d.setHours(0,0,0,0);
      let midnight = d.getTime() / 1000
      jQuery.each(data.list, function(i,v){
        if ( v.last_report < midnight ) {
          list.count++
          list.list.push(v)
        }
      })

      list.list = _.sortBy(list.list, sort )
      if ( 'today_desc' === type ){
        list.list = _.reverse( list.list )
      }
      break
    case 'oldest':
    case 'newest':
      let fd = new Date(0); // The 0 there is the key, which sets the date to the epoch

      jQuery.each(data.list, function(i,v){
        list.count++
        if ( 0 ===  v.last_report ) {
          v.meta = 'never'
        } else {
          v.meta = moment.unix(v.last_report).format('MMMM Do')
        }
        list.list.push(v)
      })
      sort = ['last_report']
      list.list = _.sortBy(list.list, sort )
      if ( 'newest' === type ){
        list.list = _.reverse( list.list )
      }
      break
    case 'most_prayed':
    case 'least_prayed':
      jQuery.each(data.list, function(i,v){
        list.count++
        v.meta = v.times_prayed
        list.list.push(v)
      })
      sort = ['times_prayed']
      list.list = _.sortBy(list.list, sort )
      if ( 'most_prayed' === type ){
        list.list = _.reverse( list.list )
      }
      break;
    case 'weekly':
    case 'monthly':
      jQuery.each(data.list, function(i,v){
        list.count++
        list.list.push(v)
      })
      break;
    case 'name':
    case 'name_desc':
      jQuery.each(data.list, function(i,v){
        list.count++
        list.list.push(v)
      })

      list.list = _.sortBy(list.list, sort )
      if ( 'name_desc' === type ){
        list.list = _.reverse( list.list )
      }
      break;
    default:
      jQuery.each(data.list, function(i,v){
        if ( v.post_type === type ) {
          list.count++
          list.list.push(v)
        }
      })
      list.list = _.sortBy(list.list, sort )
      break
  }

  return list
}

window.load_list = ( data ) => {

  let spinner = jQuery('.loading-spinner')
  let content = jQuery('#content')
  content.append(`<div class="cell center" style="background:whitesmoke;text-transform:capitalize;">${data.label} <span>(${data.count})</span></div>`)

  let icon = ''
  jQuery.each(data.list, function(ii,vv){
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
                            <i class="${icon}"></i> <span class="item-name">${vv.name}</span> <span class="item-meta">${vv.meta}</span>
                        </div>
                     </div>
      `)
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

  spinner.removeClass('active')
}

window.load_menu = () => {
  let data = window.current_list
  let category_list = jQuery('#category-list')
  jQuery.each(data.totals, function(i,v){
    category_list.append(`<li class="list-item" data-type="${i}">${i} (${v})</li>`)
  })

  let ordered = jQuery('#ordered-list')
  let ordered_list = { name: "Name", name_desc: "Name (Z-A)", most_prayed: "Most Prayed", least_prayed: "Least Prayed", oldest: "Oldest", newest: "Newest"}
  jQuery.each(ordered_list, function(i,v){
    ordered.append(`<li class="list-item" data-type="${i}">${v}</li>`)
  })

  let today = jQuery('#today-list')
  let today_list = { today: "Today", today_desc: "Today (Z-A)"}
  jQuery.each(today_list, function(i,v){
    today.append(`<li class="list-item" data-type="${i}">${v}</li>`)
  })

  // jQuery('#today-list').html(`<li class="list-item" data-type="today">Today</li>`)

  jQuery('.list-item').on('click', function(e){
    let content = jQuery('#content')
    content.empty()
    let spinner = jQuery('.loading-spinner')
    spinner.addClass('active')
    let type = jQuery(this).data('type')
    window.load_list( window.prepare_list( type) )
    jQuery('#offCanvasLeft').foundation('close')
    spinner.removeClass('active')
  })

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
