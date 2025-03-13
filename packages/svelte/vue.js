export default {
  install(app, options) {
    app.mixin({
      props: {
        fusion: Object,
      }
    })
  }
}