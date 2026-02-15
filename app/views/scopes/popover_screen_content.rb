# frozen_string_literal: true

module Terminus
  module Views
    module Scopes
      # Provides customized popover content.
      class PopoverScreenContent < Hanami::View::Scope
        def dom_id = "popover-screen-#{id}"

        def width = locals.fetch __method__, 960

        def height = locals.fetch __method__, 680

        def render(path = "shared/popovers/content/screen") = super
      end
    end
  end
end
