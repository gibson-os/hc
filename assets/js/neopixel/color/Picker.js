Ext.define('GibsonOS.module.hc.neopixel.color.Picker', {
    extend: 'GibsonOS.picker.Color',
    alias: ['widget.gosModuleHcNeopixelColorPicker'],
    initComponent: function() {
        let me = this;

        me.colors = [
            "000000", "993300", "333300", "003300", "003366",
            "000088", "333399", "333333", "880000", "FF6600",
            "888800", "008800", "008888", "0000FF", "666699",
            "888888", "FF0000", "FF9900", "99CC00", "339966",
            "33CCCC", "3366FF", "880088", "999999", "FF00FF",
            "FFCC00", "FFFF00", "00FF00", "00FFFF", "00CCFF",
            "993366", "CCCCCC", "FF99CC", "FFCC99", "FFFF99",
            "CCFFCC", "CCFFFF", "99CCFF", "CC99FF", "FFFFFF"
        ];

        me.callParent();
    }
});