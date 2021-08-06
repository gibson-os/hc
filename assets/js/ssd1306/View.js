Ext.define('GibsonOS.module.hc.ssd1306.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcSsd1306View'],
    requiredPermission: {
        module: 'hc',
        task: 'ssd1306'
    },
    cls: 'hcSsd1306View',
    data: [],
    itemSelector: 'div.hcSsd1306Pixel',
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ssd1306.store.View();
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<tpl if="bit == 0">',
                    '<tpl if="column == 0">',
                        '<div class="hcSsd1306Page">',
                    '</tpl>',
                    '<div class="hcSsd1306Column">',
                '</tpl>',
                '<div class="hcSsd1306Pixel">',
                    '<div class="hcSsd1306Pixel<tpl if="on">On<tpl else>Off</tpl>"></div>',
                '</div>',
                '<tpl if="bit == 7">',
                    '</div>',
                    '<tpl if="column == 127 && bit == 7"></div></tpl>',
                '</tpl>',
            '</tpl>'
        );

        me.callParent();

        let lastEnteredId = null;
        let lastEnteredRecord = null;
        let setOn = true;

        const getId = (record) => {
            return record.get('page') + '' + record.get('column') + '' + record.get('bit');
        }

        me.on('itemmousedown', function (view, record, item, index, event) {
            lastEnteredId = getId(record);
            lastEnteredRecord = record;
            setOn = event.button === 0;
        });
        me.on('itemcontextmenu', function (view, record, item, index, event) {
            event.stopEvent();
        });
        me.on('itemmouseup', function (view) {
            me.setLoading(true);
            lastEnteredRecord.set('on', setOn);
            lastEnteredId = null;
            lastEnteredRecord = null;
            let data = {};

            Ext.iterate(view.getStore().getModifiedRecords(), function (record) {
                const page = record.get('page');
                const column = record.get('column');

                if (!data[page]) {
                    data[page] = {};
                }

                if (!data[page][column]) {
                    data[page][column] = {};
                }

                data[page][column][record.get('bit')] = record.get('on');
            });

            GibsonOS.Ajax.request({
                url: baseDir + 'hc/ssd1306/change',
                params:  {
                    moduleId: me.hcModuleId,
                    data: Ext.encode(data)
                },
                success(response) {
                    view.getStore().commitChanges();
                },
                callback() {
                    me.setLoading(false);
                }
            });
        });
        me.on('itemmouseenter', function (view, record) {
            if (lastEnteredId === null) {
                return;
            }

            const id = getId(record);

            if (lastEnteredId === id) {
                return;
            }

            record.set('on', setOn);
            lastEnteredRecord.set('on', setOn);
            lastEnteredId = id;
            lastEnteredRecord = record;
        });
    }
});