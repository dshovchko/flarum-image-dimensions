import app from 'flarum/admin/app';

app.initializers.add('dshovchko/flarum-image-dimensions', () => {
  app.extensionData
    .for('dshovchko-image-dimensions')
    .registerSetting({
      setting: 'dshovchko-image-dimensions.scheduled_enabled',
      type: 'boolean',
      label: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_enabled_label'),
      help: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_enabled_help'),
    })
    .registerSetting({
      setting: 'dshovchko-image-dimensions.scheduled_frequency',
      type: 'select',
      label: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_frequency_label'),
      options: {
        daily: app.translator.trans('dshovchko-image-dimensions.admin.frequency_daily'),
        weekly: app.translator.trans('dshovchko-image-dimensions.admin.frequency_weekly'),
        monthly: app.translator.trans('dshovchko-image-dimensions.admin.frequency_monthly'),
      },
      default: 'weekly',
    })
    .registerSetting({
      setting: 'dshovchko-image-dimensions.scheduled_mode',
      type: 'select',
      label: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_mode_label'),
      help: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_mode_help'),
      options: {
        fast: app.translator.trans('dshovchko-image-dimensions.admin.mode_fast'),
        default: app.translator.trans('dshovchko-image-dimensions.admin.mode_default'),
        full: app.translator.trans('dshovchko-image-dimensions.admin.mode_full'),
      },
      default: 'fast',
    })
    .registerSetting({
      setting: 'dshovchko-image-dimensions.scheduled_chunk',
      type: 'number',
      label: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_chunk_label'),
      help: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_chunk_help'),
      min: 1,
      default: 100,
    })
    .registerSetting({
      setting: 'dshovchko-image-dimensions.scheduled_emails',
      type: 'text',
      label: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_emails_label'),
      help: app.translator.trans('dshovchko-image-dimensions.admin.scheduled_emails_help'),
      placeholder: 'admin@example.com, moderator@example.com',
    });
});
