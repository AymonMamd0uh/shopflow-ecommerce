
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeft, CheckCircle, ShoppingBag } from 'lucide-react'
import Navbar from '../components/Navbar'
import { useCart } from '../context/useCart'
import api from '../services/api'

export default function Checkout() {
  const navigate = useNavigate()
  const { cart, loading } = useCart()

  const [form, setForm] = useState({
    customer_name: '',
    customer_phone: '',
    shipping_city: '',
    shipping_address: '',
    notes: '',
  })

  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState(false)

  const handleChange = (e) => {
    setForm({
      ...form,
      [e.target.name]: e.target.value,
    })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      await api.post('/checkout', form)

      setSuccess(true)
    } catch (err) {
      const validationErrors = err.response?.data?.errors

      if (validationErrors) {
        setError(Object.values(validationErrors).flat().join(' '))
      } else {
        setError(err.response?.data?.message || 'Checkout failed. Please try again.')
      }
    } finally {
      setSubmitting(false)
    }
  }

  const items = cart?.items || []

  return (
    <div className="min-h-screen bg-slate-50">
      <Navbar />

      <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <button
          onClick={() => navigate('/cart')}
          className="mb-6 flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700"
        >
          <ArrowLeft size={17} />
          Back to Cart
        </button>

        {success ? (
          <div className="rounded-2xl border border-green-200 bg-white p-10 text-center">
            <CheckCircle className="mx-auto mb-4 text-green-500" size={60} />
            <h1 className="text-2xl font-bold text-slate-900">
              Order Placed Successfully!
            </h1>
            <p className="mt-3 text-slate-500">
              Thank you for shopping with ShopFlow.
            </p>
            <button
              onClick={() => navigate('/')}
              className="mt-6 rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700"
            >
              Continue Shopping
            </button>
          </div>
        ) : (
          <div className="grid gap-8 lg:grid-cols-3">
            <form
              onSubmit={handleSubmit}
              className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2"
            >
              <div>
                <h1 className="text-2xl font-bold text-slate-900">
                  Checkout
                </h1>
                <p className="mt-2 text-sm text-slate-500">
                  Enter your shipping information.
                </p>
              </div>

              {error && (
                <div className="rounded-xl bg-red-50 p-4 text-sm text-red-600">
                  {error}
                </div>
              )}

              <div>
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  Customer Name
                </label>
                <input
                  name="customer_name"
                  value={form.customer_name}
                  onChange={handleChange}
                  required
                  minLength={2}
                  maxLength={255}
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                  placeholder="Enter your full name"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  Phone Number
                </label>
                <input
                  name="customer_phone"
                  type="tel"
                  value={form.customer_phone}
                  onChange={handleChange}
                  required
                  minLength={7}
                  maxLength={30}
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                  placeholder="Enter your phone number"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  City
                </label>
                <input
                  name="shipping_city"
                  value={form.shipping_city}
                  onChange={handleChange}
                  required
                  minLength={2}
                  maxLength={255}
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                  placeholder="Enter your city"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  Shipping Address
                </label>
                <textarea
                  name="shipping_address"
                  value={form.shipping_address}
                  onChange={handleChange}
                  required
                  minLength={5}
                  maxLength={1000}
                  rows={3}
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                  placeholder="Street, building, apartment..."
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  Notes (Optional)
                </label>
                <textarea
                  name="notes"
                  value={form.notes}
                  onChange={handleChange}
                  maxLength={2000}
                  rows={3}
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                  placeholder="Any additional information..."
                />
              </div>

              <button
                type="submit"
                disabled={submitting || loading || items.length === 0}
                className="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {submitting ? 'Placing Order...' : 'Place Order'}
              </button>
            </form>

            <div className="h-fit rounded-2xl border border-slate-200 bg-white p-6">
              <h2 className="text-xl font-bold text-slate-900">
                Order Summary
              </h2>

              <div className="mt-5 space-y-4">
                {items.map((item) => (
                  <div key={item.id} className="flex justify-between gap-3 text-sm">
                    <div>
                      <p className="font-semibold text-slate-800">
                        {item.product.name}
                      </p>
                      <p className="text-slate-500">
                        Quantity: {item.quantity}
                      </p>
                    </div>

                    <span className="font-semibold text-slate-900">
                      {Number(item.subtotal).toLocaleString('en-US')} EGP
                    </span>
                  </div>
                ))}
              </div>

              <div className="my-5 border-t border-slate-200" />

              <div className="flex justify-between font-bold text-slate-900">
                <span>Total</span>
                <span>
                  {Number(cart?.total || 0).toLocaleString('en-US')} EGP
                </span>
              </div>

              <div className="mt-5 flex items-center gap-2 text-sm text-slate-500">
                <ShoppingBag size={17} />
                Your order will be processed after confirmation.
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  )
}
