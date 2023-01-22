import React, { useRef, useMemo } from 'react'
import {PageTable, Permission, route, UrlSwitch} from '@/duxweb'
import {Button} from '@arco-design/web-react'

export default function Table() {
  const table = useRef(null)

  const columns = useMemo(() => {
    return [
      {
        dataIndex: 'name',
        title: '名称'
      },
      {
        dataIndex: 'op',
        title: '操作',
        width: 180,
        render: (_, record) => (
          <>
            <Permission mark=''>
              <Button
                status='primary'
                type='text'
                size='small'
                onClick={async () => {
                  const status = await route.modal(
                      '{{pageUrl}}',
                      {
                        id: record.id
                      },
                      {
                        title: '模板编辑'
                      }
                    )
                    .getData()
                  if (status) {
                    table.current.reload()
                  }
                }}
              >
                编辑
              </Button>
            </Permission>
            <Permission mark=''>
              <Button status='danger' type='text'  size='small'>
                删除
              </Button>
            </Permission>
          </>
        )
      }
    ]
  }, [])

  return (
    <PageTable
      ref={table}
      title='列表页面'
      url='{{routeUrl}}'
      primaryKey='id'
      columns={columns}
      menus={[
        <Permission key='add' mark=''>
          <Button
            type='primary'
            onClick={async () => {
              const status = await route
                .modal(
                  '{{pageUrl}}',
                  {
                    page: 1
                  },
                  {
                    title: '模板添加',
                  },
                )
                .getData()
              if (status) {
                table.current.reload()
              }
            }}
          >
              新建
          </Button>
        </Permission>
      ]}
    ></PageTable>
  )
}
