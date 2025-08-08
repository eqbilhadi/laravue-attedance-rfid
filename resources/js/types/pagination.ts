export interface Pagination<T> {
  data: T[]
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
  links: {
    url: string | null
    label: string
    active: boolean
  }[]
}

