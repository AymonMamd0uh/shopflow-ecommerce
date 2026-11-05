
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { User, Mail, LockKeyhole, ShoppingBag } from 'lucide-react'
import api from '../services/api'

export default function Register() {
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')

    if (form.password !== form.password_confirmation) {
      setError('Passwords do not match.')
      return
    }

    setLoading(true)

    try {
      const response = await api.post('/auth/register', form)

      const token = response.data.data?.token || response.data.token

      if (!token) {
        throw new Error('Account created, but no token was returned.')
      }

      localStorage.setItem('token', token)
      navigate('/')
    } catch (err) {
      const errors = err.response?.data?.errors

      setError(
        errors
          ? Object.values(errors).flat().join(' ')
          : err.response?.data?.message || err.message
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
      <div className="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-8 shadow-xl">

        <Link to="/" className="mb-7 flex items-center justify-center gap-2">
          <ShoppingBag className="text-blue-600" size={30} />
          <span className="text-2xl font-extrabold text-slate-900">
            ShopFlow<span className="text-blue-600">.</span>
          </span>
        </Link>

        <div className="mb-7 text-center">
          <h1 className="text-3xl font-extrabold text-slate-900">
            Create Account
          </h1>
          <p className="mt-2 text-slate-500">
            Join ShopFlow and start shopping.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">Full Name</label>
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-4">
              <User size={19} className="text-slate-400" />
              <input name="name" value={form.name} onChange={handleChange}
                placeholder="Your name" required
                className="w-full py-3 outline-none" />
            </div>
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">Email Address</label>
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-4">
              <Mail size={19} className="text-slate-400" />
              <input type="email" name="email" value={form.email} onChange={handleChange}
                placeholder="you@example.com" required
                className="w-full py-3 outline-none" />
            </div>
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">Password</label>
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-4">
              <LockKeyhole size={19} className="text-slate-400" />
              <input type="password" name="password" value={form.password} onChange={handleChange}
                placeholder="At least 8 characters" required minLength={8}
                className="w-full py-3 outline-none" />
            </div>
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</label>
            <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-4">
              <LockKeyhole size={19} className="text-slate-400" />
              <input type="password" name="password_confirmation"
                value={form.password_confirmation} onChange={handleChange}
                placeholder="Repeat your password" required
                className="w-full py-3 outline-none" />
            </div>
          </div>

          {error && (
            <div className="rounded-xl bg-red-50 p-3 text-sm text-red-600">
              {error}
            </div>
          )}

          <button type="submit" disabled={loading}
            className="w-full rounded-xl bg-blue-600 py-3.5 font-bold text-white transition hover:bg-blue-700 disabled:opacity-60">
            {loading ? 'Creating Account...' : 'Create Account'}
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-500">
          Already have an account?{' '}
          <Link to="/login" className="font-bold text-blue-600 hover:underline">
            Sign In
          </Link>
        </p>

      </div>
    </div>
  )
}
