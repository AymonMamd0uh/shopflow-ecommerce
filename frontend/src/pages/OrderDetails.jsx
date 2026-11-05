
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowLeft, Package, MapPin, CreditCard } from 'lucide-react'
import Navbar from '../components/Navbar'
import api from '../services/api'

export default function OrderDetails() {
  const { id } = useParams()

  const [order, setOrder] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    const fetchOrder = async () => {
      try {
        const response = await api.get(`/orders/${id}`)
        setOrder(response.data.data)
      } catch (err) {
        setError(
          err.response?.data?.message || 'Unable to load order details.'
        )
      } finally {
        setLoading(false)
      }
    }

    fetchOrder()
  }, [id])

  const formatPrice = (price) =>
    `${Number(price || 0).toLocaleString('en-US')} EGP`

  const formatStatus = (status) => {
    const colors = {
      pending: 'bg-yellow-50 text-yellow-700',
      confirmed: 'bg-blue-50 text-blue-700',
      processing: 'bg-purple-50 text-purple-700',
      shipped: 'bg-indigo-50 text-indigo-700',
      delivered: 'bg-green-50 text-green-700',
      returned: 'bg-orange-50 text-orange-700',
      cancelled: 'bg-red-50 text-red-700',
    }

    return colors[status] || 'bg-slate-100 text-slate-700'
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <Navbar />

      <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <Link
          to="/my-orders"
          className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700"
        >
          <ArrowLeft size={17} />
          Back to My Orders
        </Link>

        {loading ? (
          <p className="py-16 text-center text-slate-500">
            Loading order details...
          </p>
        ) : error ? (
          <div className="rounded-xl bg-red-50 p-5 text-red-600">
            {error}
          </div>
        ) : order ? (
          <div className="space-y-6">
            <div className="rounded-2xl border border-slate-200 bg-white p-6">
              <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                  <h1 className="text-2xl font-bold text-slate-900">
                    Order Details
                  </h1>
                  <p className="mt-2 text-slate-500">
                    {order.order_number || `Order #${order.id}`}
                  </p>
                  <p className="mt-1 text-sm text-slate-400">
                    {order.created_at
                      ? new Date(order.created_at).toLocaleString()
                      : ''}
                  </p>
                </div>

                <span className={`w-fit rounded-full px-4 py-2 text-sm font-semibold capitalize ${formatStatus(order.status)}`}>
                  {order.status}
                </span>
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-6">
              <div className="mb-5 flex items-center gap-2">
                <Package className="text-blue-600" size={22} />
                <h2 className="text-xl font-bold text-slate-900">
                  Order Items
                </h2>
              </div>

              <div className="space-y-4">
                {(order.items || []).map((item) => (
                  <div
                    key={item.id}
                    className="flex flex-col justify-between gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center"
                  >
                    <div>
                      <h3 className="font-semibold text-slate-900">
                        {item.product_name}
                      </h3>
                      <p className="mt-1 text-sm text-slate-500">
                        SKU: {item.product_sku || 'N/A'}
                      </p>
                      <p className="mt-1 text-sm text-slate-500">
                        {formatPrice(item.unit_price)} × {item.quantity}
                      </p>
                    </div>

                    <p className="font-bold text-slate-900">
                      {formatPrice(item.total)}
                    </p>
                  </div>
                ))}
              </div>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
              <div className="rounded-2xl border border-slate-200 bg-white p-6">
                <div className="mb-5 flex items-center gap-2">
                  <MapPin className="text-blue-600" size={22} />
                  <h2 className="text-xl font-bold text-slate-900">
                    Shipping Information
                  </h2>
                </div>

                <div className="space-y-3 text-sm">
                  <p>
                    <span className="text-slate-500">Name: </span>
                    <strong>{order.customer_name}</strong>
                  </p>
                  <p>
                    <span className="text-slate-500">Phone: </span>
                    <strong>{order.customer_phone}</strong>
                  </p>
                  <p>
                    <span className="text-slate-500">City: </span>
                    <strong>{order.shipping_city}</strong>
                  </p>
                  <p>
                    <span className="text-slate-500">Address: </span>
                    <strong>{order.shipping_address}</strong>
                  </p>

                  {order.notes && (
                    <p>
                      <span className="text-slate-500">Notes: </span>
                      <strong>{order.notes}</strong>
                    </p>
                  )}
                </div>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6">
                <div className="mb-5 flex items-center gap-2">
                  <CreditCard className="text-blue-600" size={22} />
                  <h2 className="text-xl font-bold text-slate-900">
                    Payment Summary
                  </h2>
                </div>

                <div className="space-y-4 text-sm">
                  <div className="flex justify-between">
                    <span className="text-slate-500">Payment Method</span>
                    <strong className="capitalize">
                      {order.payment_method?.replaceAll('_', ' ')}
                    </strong>
                  </div>

                  <div className="flex justify-between">
                    <span className="text-slate-500">Payment Status</span>
                    <strong className="capitalize">
                      {order.payment_status}
                    </strong>
                  </div>

                  <div className="flex justify-between">
                    <span className="text-slate-500">Subtotal</span>
                    <strong>{formatPrice(order.subtotal)}</strong>
                  </div>

                  <div className="flex justify-between">
                    <span className="text-slate-500">Shipping Fee</span>
                    <strong>{formatPrice(order.shipping_fee)}</strong>
                  </div>

                  <div className="border-t border-slate-200 pt-4">
                    <div className="flex justify-between text-lg font-bold">
                      <span>Total</span>
                      <span className="text-blue-600">
                        {formatPrice(order.total)}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        ) : null}
      </main>
    </div>
  )
}
