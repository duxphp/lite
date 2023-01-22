import React from 'react'
import {useRouter, ModalForm} from '@/duxweb'
import {Input, Form as ArcoForm} from '@arco-design/web-react'
const FormItem = ArcoForm.Item

export default function Form() {
  const {params} = useRouter()

  return (
    <ModalForm url={`{{routeUrl}}/${params.id || 0}`} type={params.id ? 'edit' : 'add'}>
      {({ data }) => (
        <>
          <FormItem label='名称' field='name' rules={[{required: true}]}>
            <Input placeholder='请输入名称'/>
          </FormItem>
        </>
      )}
    </ModalForm>
  )
}
