
// const staticList = "PrayerCalendar"
//
//
// self.addEventListener("install", installEvent => {
//   installEvent.waitUntil(
//     caches.open(staticList).then(cache => {
//       cache.addAll(jsObject.list)
//     })
//   )
// })

// self.addEventListener("fetch", fetchEvent => {
//   fetchEvent.respondWith(
//     caches.match(fetchEvent.request).then(res => {
//       return res || fetch(fetchEvent.request)
//     })
//   )
// })


// var CACHE_NAME = 'dependencies-cache';
