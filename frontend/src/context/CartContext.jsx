import { useEffect, useState } from 'react'
import api from '../services/api'
import CartContext from './cartContext'

export function CartProvider({ children }) {
  const [cart, setCart] = useState(null)
  const [loading, setLoading] = useState(() =>
    Boolean(localStorage.getItem('token'))
  )

  const fetchCart = async () => {
    if (!localStorage.getItem('token')) {
      setCart(null)
      setLoading(false)
      return
    }

    setLoading(true)

    try {
      const response = await api.get('/cart')
      setCart(response.data.data)
    } catch (error) {
      console.error('Failed to load cart:', error)
    } finally {
      setLoading(false)
    }
  }

  const addToCart = async (productId, quantity = 1) => {
    const response = await api.post('/cart/items', {
      product_id: productId,
      quantity,
    })

    await fetchCart()
    return response.data
  }

  const updateCartItem = async (itemId, quantity) => {
    await api.patch(`/cart/items/${itemId}`, { quantity })
    await fetchCart()
  }

  const removeCartItem = async (itemId) => {
    await api.delete(`/cart/items/${itemId}`)
    await fetchCart()
  }

  const clearCart = async () => {
    await api.delete('/cart')
    await fetchCart()
  }

  useEffect(() => {
    if (!localStorage.getItem('token')) return

    let cancelled = false

    const loadCart = async () => {
      try {
        const response = await api.get('/cart')

        if (!cancelled) {
          setCart(response.data.data)
        }
      } catch (error) {
        if (!cancelled) {
          console.error('Failed to load cart:', error)
        }
      } finally {
        if (!cancelled) {
          setLoading(false)
        }
      }
    }

    loadCart()

    return () => {
      cancelled = true
    }
  }, [])

  return (
    <CartContext.Provider
      value={{
        cart,
        loading,
        fetchCart,
        addToCart,
        updateCartItem,
        removeCartItem,
        clearCart,
      }}
    >
      {children}
    </CartContext.Provider>
  )
}