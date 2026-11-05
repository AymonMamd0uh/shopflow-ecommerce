
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LockKeyhole, Mail, ShoppingBag } from 'lucide-react'
import api from '../services/api'

export default function Login() {
  const navigate = useNavigate()

  const [form, setForm] = useState({
    email: '',
    password: '',
  })

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const response = await api.post('/auth/login', form)

      const token =
        response.data.data?.token ||
        response.data.token

      if (!token) {
        throw new Error('Login succeeded but no token was returned.')
      }

      localStorage.setItem('token', token)
      navigate('/')
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.message ||
        'Invalid email or password.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
      <div className="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-8 shadow-xl shadow-slate-200/50">

        <Link to="/" className="mb-8 flex items-center justify-center gap-2">
          <ShoppingBag className="text-blue-600" size={30} />
          <span className="text-2xl font-extrabold text-slate-900">
            ShopFlow<span className="text-blue-600">.</span>
          </span>
        </Link>

        <div className="mb-8 text-center">
          <h1 className="text-3xl font-extrabold text-slate-900">
            Welcome Back!
          </h1>
          <p className="mt-2 text-slate-500">
            Sign in to continue shopping.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Email Address
            </label>
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-4 focus-within:border-blue-500">
              <Mail size={19} className="text-slate-400" />
              <input
                type="email"
                name="email"
                value={form.email}
                onChange={handleChange}
                placeholder="you@example.com"
                required
                className="w-full py-3 outline-none"
              />
            </div>
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Password
            </label>
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-4 focus-within:border-blue-500">
              <LockKeyhole size={19} className="text-slate-400" />
              <input
                type="password"
                name="password"
                value={form.password}
                onChange={handleChange}
                placeholder="Enter your password"
                required
                className="w-full py-3 outline-none"
              />
            </div>
          </div>

          {error && (
            <div className="rounded-xl bg-red-50 p-3 text-sm text-red-600">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-xl bg-blue-600 py-3.5 font-bold text-white transition hover:bg-blue-700 disabled:opacity-60"
          >
            {loading ? 'Signing in...' : 'Sign In'}
          </button>

        </form>

        <p className="mt-7 text-center text-sm text-slate-500">
          Don't have an account?{' '}
          <Link to="/register" className="font-bold text-blue-600 hover:underline">
            Create Account
          </Link>
        </p>

        <Link to="/" className="mt-5 block text-center text-sm text-slate-400 hover:text-blue-600">
          Back to Store
        </Link>

      </div>
    </div>
  )
}
