Ext.define('GibsonOS.module.hc.bme280.Panel', {
    extend: 'GibsonOS.Panel',
    alias: ['widget.gosModuleHcBme280Panel'],
    requiredPermission: {
        module: 'hc',
        task: 'bme280'
    },
    cls: 'coloredPanel',
    data: [],
    initComponent: function() {
        var me = this;

        me.tpl = new Ext.XTemplate(
            '<div class="bme280Temperature">Temperatur: {temperature} °C</div>',
            '<div class="bme280Pressure">Luftdruck: {pressure} hPa</div>',
            '<div class="bme280Humidity">Luftfeuchtigkeit: {humidity} %</div>'
        );
        me.tbar = [{
            text: 'Messen',
            requiredPermission: {
                action: 'measure',
                method: 'GET',
                permission: GibsonOS.Permission.READ
            },
            handler: function() {
                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/bme280/measure',
                    method: 'GET',
                    params:  {
                        moduleId: me.gos.data.module.id
                    },
                    success: function(response) {
                        me.update(Ext.decode(response.responseText).data);
                    }
                });
            }
        }];

        me.callParent();
    }
});