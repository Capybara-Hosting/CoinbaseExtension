# Coinbase Commerce Extension for Paymenter

This extension integrates Coinbase Commerce cryptocurrency payments into your Paymenter installation.

## Features

- Accept payments in Bitcoin, Ethereum, USDC, and other major cryptocurrencies
- Secure webhook handling for payment status updates
- Automatic invoice processing
- Professional payment interface
- Support for both test and production environments

## Prerequisites

1. A Coinbase Commerce account at [commerce.coinbase.com](https://beta.commerce.coinbase.com/)
2. An EVM-compatible wallet address for receiving crypto deposits
3. API key from Coinbase Commerce
4. Webhook secret for secure communication

## Installation

1. Ensure the extension files are in the `extensions/Gateways/Coinbase-Commerce/` directory
2. The extension will be automatically detected by Paymenter

## Configuration

### 1. Get Your API Key

1. Log into [Coinbase Commerce](https://beta.commerce.coinbase.com/)
2. Go to **Settings > Security**
3. Click **New API key** and copy your key

### 2. Get Your Webhook Secret

1. Go to **Settings > Notifications**
2. Copy your webhook shared secret

### 3. Configure in Paymenter Admin

1. Go to **Admin > Extensions > Gateways**
2. Create a new gateway or edit existing one
3. Select **Coinbase Commerce** as the gateway type
4. Fill in the configuration:
   - **API Key**: Your Coinbase Commerce API key
   - **Webhook Secret**: Your webhook shared secret
   - **Test Mode**: Enable for development/testing
   - **Charge Reuse Window (Hours)**: How many hours to wait before creating a new charge (prevents duplicate charges)

### 4. Set Up Webhook URL

1. In Coinbase Commerce, go to **Settings > Notifications**
2. Add a new webhook endpoint with URL:
   ```
   https://yourdomain.com/extensions/coinbasecommerce/webhook
   ```
3. Select the following events:
   - `charge:created`
   - `charge:pending`
   - `charge:confirmed`
   - `charge:failed`

## How It Works

### Payment Flow

1. Customer selects Coinbase Commerce as payment method
2. Extension checks for existing charges within the configured time window
3. If a valid charge exists, it reuses the existing payment URL
4. If no valid charge exists, it creates a new charge on Coinbase Commerce
5. Customer is redirected to Coinbase's hosted payment page
6. Customer completes payment with their preferred cryptocurrency
7. Coinbase sends webhook notifications for payment status updates
8. Extension processes webhooks and updates invoice status

### Charge Reuse Logic

The extension intelligently reuses existing charges to prevent duplicate charge creation:
- **Time Window**: Configurable hours (default: 1 hour)
- **Validation**: Checks charge status, expiration, and amount match
- **Benefits**: Reduces API calls, prevents duplicate charges, improves user experience

### Webhook Processing

The extension handles these webhook events:

- **`charge:created`**: Payment initiated
- **`charge:pending`**: Payment received but not yet confirmed
- **`charge:confirmed`**: Payment fully confirmed and finalized
- **`charge:failed`**: Payment could not be completed

## Security Features

- Webhook signature verification using HMAC SHA256
- CSRF protection disabled only for webhook endpoint
- Comprehensive logging for debugging and monitoring
- Duplicate payment prevention

## Testing

1. Enable **Test Mode** in the configuration
2. Use Coinbase Commerce test environment
3. Test with small amounts first
4. Monitor logs for any errors

## Troubleshooting

### Common Issues

1. **Webhook not receiving events**
   - Verify webhook URL is accessible
   - Check webhook secret is correct
   - Ensure HTTPS is enabled

2. **Payment not processing**
   - Check API key is valid
   - Verify webhook signature verification
   - Review application logs

3. **Invoice not updating**
   - Confirm webhook events are being received
   - Check invoice properties for charge ID
   - Verify webhook processing logic

### Logs

The extension logs all activities. Check your Laravel logs for:
- Payment creation attempts
- Webhook reception and processing
- Error messages and stack traces

## Support

For issues with this extension:
1. Check the logs for error messages
2. Verify your Coinbase Commerce configuration
3. Ensure webhook endpoints are properly configured

For Coinbase Commerce support:
- Visit [Coinbase Commerce Help](https://help.coinbase.com/en/commerce)
- Check [API Documentation](https://docs.commerce.coinbase.com/)

## Changelog

### Version 1.0.0
- Initial release
- Basic payment processing
- Webhook handling
- Payment interface
- Security features

## License

This extension is part of the Paymenter ecosystem and follows the same licensing terms.
