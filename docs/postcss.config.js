import rtlcss from 'postcss-rtlcss'

export default {
  plugins: [
    rtlcss({
      ltrPrefix: ':where([dir="ltr"])',
      rtlPrefix: ':where([dir="rtl"])'
    })
  ]
}
