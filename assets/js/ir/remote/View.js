Ext.define('GibsonOS.module.hc.ir.remote.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcIrRemoteView'],
    multiSelect: false,
    singleSelect: true,
    trackOver: true,
    itemSelector: 'div.hcIrRemoteItem',
    selectedItemCls: 'hcIrRemoteItemSelected',
    overItemCls: 'hcIrRemoteItemHover',
    remote: {
        name: null,
        itemWidth: 30,
        width: 0,
        height: 0
    },
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ir.store.RemoteKey({
            remoteId: me.remote.id
        });
        me.tpl = new Ext.XTemplate(
            '{[this.container()]}',
            '<tpl for=".">',
                '{[this.renderButton(values)]}',
            '</tpl>',
            '</div>',
            {
                container() {
                    const container = document.getElementById('hcIrRemoteContainer' + me.getId());
                    const width = me.remote.width * me.remote.itemWidth + 10;
                    const height = me.remote.height * me.remote.itemWidth + 10;

                    if (container) {
                        container.style.width = width + 'px';
                        container.style.height = height + 'px';
                    }

                    return '<div '
                        + 'id="hcIrRemoteContainer' + me.getId() + '" '
                        + 'class="hcIrRemoteContainer" '
                        + 'style="width: ' + width + 'px; height: ' + height + 'px;"'
                        + '>';
                },
                renderButton(values) {
                    console.log(values);
                    let width = values.width * me.remote.itemWidth - 4;
                    let height = values.height * me.remote.itemWidth - 4;
                    let textTopMargin = (height - 14) / 2-1;
                    let borders = '';
                    let top = values.top * me.remote.itemWidth + 6;
                    let left = values.left * me.remote.itemWidth + 6;
                    let borderWidth = [1, 1, 1, 1];
                    let sizeAdd = [0, 0, 0, 0];
                    let borderRadius = [0, 0, 0, 0];
                    let background = 'none';
                    console.log(1);

                    switch (values.style) {
                        case 1:
                            borderRadius = [20, 20, 20, 20];
                            break;
                        case 2:
                            borderRadius = [50, 50, 50, 50];
                            break;
                        case 3:
                            borderWidth = [0, 0, 0, 0];
                            break;
                    }
                    console.log(2);
                    if (values.background) {
                        background = '#' + values.background;
                    }
                    console.log(3);
                    if (values.docked) {
                        Ext.iterate(values.docked, function(dock) {
                            let id = -1;

                            switch (dock) {
                                case 'top':
                                    id = 0;
                                    break;
                                case 'right':
                                    id = 1;
                                    break;
                                case 'bottom':
                                    id = 2;
                                    break;
                                case 'left':
                                    id = 3;
                                    break;
                            }

                            if (id > -1) {
                                borderRadius[id] = 0;

                                if (id < 3) {
                                    borderRadius[id+1] = 0;
                                } else {
                                    borderRadius[0] = 0;
                                }

                                borderWidth[id] = 0;
                                sizeAdd[id] = 2;
                            }
                        });
                    }
                    console.log(4);
                    textTopMargin += sizeAdd[0] - borderWidth[0] + 1;
                    top -= sizeAdd[0];
                    width += sizeAdd[1] + sizeAdd[3];
                    height += sizeAdd[0] + sizeAdd[2];
                    left -= sizeAdd[3];

                    borders = 'border-radius:';
                    console.log(5);
                    Ext.iterate(borderRadius, function(radius) {
                        borders += ' ' + radius + '%';
                    });

                    borders += ';';
                    borders += 'border-width:';
                    console.log(5);
                    Ext.iterate(borderWidth, function(width) {
                        borders += ' ' + width + 'px';
                    });

                    borders += ';';
                    borders += 'border-style: solid;';
                    console.log(7);
                    let style =
                        'top: ' + top + 'px;' +
                        'left: ' + left + 'px;' +
                        'width: ' + width + 'px;' +
                        'height: ' + height + 'px;' +
                        'background: ' + background + ';' +
                        borders
                    ;
                    console.log(8);
                    if (
                        values.keys ||
                        values.eventId
                    ) {
                        style += 'cursor: pointer;';
                    }
                    console.log(9);
                    return '<div ' +
                        'class="hcIrRemoteItem" ' +
                        'style="' + style + '" ' +
                        'title="' + values.name + '"' +
                    '>' +
                        '<div style="margin-top: ' + textTopMargin + 'px;">' + values.name + '</div>' +
                    '</div>';
                }
            },
        );

        me.callParent();
    },
});