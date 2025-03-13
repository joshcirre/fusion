import {unref} from 'vue'

export default function syncQueryString({fusion, state, action}, next) {
  const {property, query} = action

  let frameCount = 0
  const maxFrames = 60 // About 1 second at 60fps

  function updateUrl() {
    const url = new URL(window.location.href)

    if (state.hasOwnProperty(property)) {
      const value = unref(state[property])

      if (!value) {
        url.searchParams.delete(query)
      } else {
        url.searchParams.set(query, value.toString())
      }
    } else {
      url.searchParams.delete(query)
    }

    const newUrl = url.pathname + (url.search || '')

    if (window.location.pathname + window.location.search !== newUrl) {
      window.history.replaceState(
        window.history.state,
        '',
        newUrl
      )
    }

    frameCount++
    if (frameCount < maxFrames) {
      requestAnimationFrame(updateUrl)
    }
  }

  requestAnimationFrame(updateUrl)

  return next({fusion, state})
}